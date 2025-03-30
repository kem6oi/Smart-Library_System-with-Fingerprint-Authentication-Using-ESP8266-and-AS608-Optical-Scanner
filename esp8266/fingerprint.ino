#include <Adafruit_Fingerprint.h>
#include <ESP8266WebServer.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <SoftwareSerial.h>

// WiFi credentials
const char* ssid = "Conman"; // ------------- Make sure this is your WiFi Network Name
const char* password = "password"; // ----------- Make sure this is your WiFi Password

// Server details
// *** UPDATED: Point to the correct PHP file ***
const char* serverUrl = "http://192.168.203.181/library0-2w/admin/fingerprint_auth.php"; // Web server IP/path

// Fingerprint sensor on D5 (GPIO-14) and D6 (GPIO-12)
SoftwareSerial mySerial(14, 12); // RX, TX --> Connect Sensor TX to D5(GPIO-14), Sensor RX to D6(GPIO-12)
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

// Web server and HTTP client
ESP8266WebServer server(80);
WiFiClient client;
HTTPClient http;

void setup() {
  Serial.begin(115200);
  while (!Serial); // Wait for serial monitor to open (optional)
  delay(100);
  Serial.println("\n\nAdafruit Fingerprint Enrollment/Verification");

  // set the data rate for the sensor serial port
  finger.begin(57600);
  delay(5);
  if (finger.verifyPassword()) {
    Serial.println("Found fingerprint sensor!");
  } else {
    Serial.println("Did not find fingerprint sensor :(");
    while (1) { delay(1); } // Halt if sensor not found
  }

  Serial.println(F("Reading sensor parameters"));
  finger.getParameters();
  Serial.print(F("Status: 0x")); Serial.println(finger.status_reg, HEX);
  Serial.print(F("Sys ID: 0x")); Serial.println(finger.system_id, HEX);
  Serial.print(F("Capacity: ")); Serial.println(finger.capacity);
  Serial.print(F("Security level: ")); Serial.println(finger.security_level);
  Serial.print(F("Device address: ")); Serial.println(finger.device_addr, HEX);
  Serial.print(F("Packet len: ")); Serial.println(finger.packet_len);
  Serial.print(F("Baud rate: ")); Serial.println(finger.baud_rate);

  // Connect to WiFi
  Serial.printf("Connecting to %s ", ssid);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP()); // *** NOTE THIS IP ADDRESS FOR THE PHP FILES ***

  // Setup Web Server Routes
  server.on("/enroll", HTTP_POST, handleEnroll);
  server.on("/verify", HTTP_GET, handleVerify);
  // Optional: Add a root handler for testing connection
  server.on("/", HTTP_GET, [](){
    server.sendHeader("Access-Control-Allow-Origin", "*"); // Added CORS
    server.send(200, "text/plain", "ESP8266 Fingerprint Server Ready");
  });

  server.begin();
  Serial.println("HTTP server started");
}

void loop() {
  server.handleClient();
  // It's better to not have delay() in loop if possible,
  // but for simple tasks, a small delay is okay.
  delay(2);
}

// Handles POST request from fingerprint_auth.php (via AJAX)
void handleEnroll() {
  Serial.println("Enroll request received");
  server.sendHeader("Access-Control-Allow-Origin", "*"); // Added CORS
  if (server.hasArg("id") && server.hasArg("studentid")) {
    int id = server.arg("id").toInt();
    String studentid = server.arg("studentid");
    Serial.printf("Attempting to enroll ID #%d for Student: %s\n", id, studentid.c_str());

    if (id < 1 || id > finger.capacity) {
       Serial.println("Invalid ID");
       server.send(400, "application/json", "{\"success\":false,\"message\":\"Invalid Fingerprint ID (must be 1-" + String(finger.capacity) + ")\"}");
       return;
    }

    uint8_t enrollResult = enrollFingerprint(id);

    if (enrollResult == FINGERPRINT_OK) {
      Serial.println("Fingerprint enrolled locally on sensor.");
      // Now, send enrolled fingerprint info to PHP server
      if (WiFi.status() == WL_CONNECTED) {
        http.begin(client, serverUrl); // Use the global client
        http.addHeader("Content-Type", "application/x-www-form-urlencoded");
        http.addHeader("X-ESP8266", "true"); // Custom header to identify ESP8266

        String postData = "register=1&studentid=" + urlencode(studentid) + "&fingerprintid=" + String(id);
        Serial.print("Sending POST to PHP: ");
        Serial.println(postData);

        int httpCode = http.POST(postData);
        String payload = http.getString();
        http.end();

        Serial.printf("PHP Server Response Code: %d\n", httpCode);
        Serial.print("PHP Server Response Payload: ");
        Serial.println(payload);

        if (httpCode == 200 && payload.indexOf("OK") != -1) { // Check for 200 OK and "OK" in body
          Serial.println("Server database updated successfully.");
          server.send(200, "application/json", "{\"success\":true,\"message\":\"Fingerprint enrolled and server updated successfully!\"}");
        } else {
          Serial.print("Server update failed! HTTP code: ");
          Serial.println(httpCode);
          // Optional: Attempt to delete the just-enrolled fingerprint from sensor if server update fails?
          // finger.deleteModel(id);
          server.send(500, "application/json", "{\"success\":false,\"message\":\"Fingerprint enrolled on sensor, but failed to update server database. Code: " + String(httpCode) + "\"}");
        }
      } else {
          Serial.println("WiFi disconnected, cannot update server.");
          server.send(500, "application/json", "{\"success\":false,\"message\":\"Fingerprint enrolled on sensor, but WiFi disconnected. Cannot update server.\"}");
      }
    } else {
      // Enrollment failed on the sensor itself
      Serial.print("Failed to enroll fingerprint on sensor, error: ");
      printFingerprintError(enrollResult);
      server.send(400, "application/json", "{\"success\":false,\"message\":\"Failed to enroll fingerprint on sensor. Error: " + String(enrollResult) + "\"}");
    }
  } else {
    Serial.println("Missing ID or StudentID parameter in enroll request");
    server.send(400, "application/json", "{\"success\":false,\"message\":\"Missing 'id' or 'studentid' parameter.\"}");
  }
}

// Handles GET request from Issue-book.php (via AJAX)
void handleVerify() {
  Serial.println("Verify request received. Scanning...");
  server.sendHeader("Access-Control-Allow-Origin", "*"); // Added CORS
  int fingerprintId = verifyFingerprint();

  if (fingerprintId >= 0) { // Found a match
    Serial.printf("Fingerprint matched, ID: %d\n", fingerprintId);
    String response = "{\"success\":true,\"fingerprintId\":" + String(fingerprintId) + "}";
    server.send(200, "application/json", response);
  } else { // No match or error
     Serial.println("Fingerprint not recognized or error occurred.");
     String message = "Fingerprint not recognized";
     if (fingerprintId == -2) message = "Error communicating with sensor";
     if (fingerprintId == -3) message = "Error reading image";
     // Add more specific errors if needed based on verifyFingerprint return values
     server.send(404, "application/json", "{\"success\":false,\"message\":\"" + message + "\"}"); // Use 404 Not Found
  }
}


// --- Fingerprint Helper Functions ---

uint8_t enrollFingerprint(uint16_t id) {
  int p = -1;
  Serial.println("Ready to enroll a fingerprint!");
  Serial.println("Please place your finger on the sensor...");
  // Wait for finger
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    switch (p) {
    case FINGERPRINT_OK:
      Serial.println("Image taken");
      break;
    case FINGERPRINT_NOFINGER:
      Serial.print(".");
      delay(100); // Don't spam serial
      break;
    case FINGERPRINT_PACKETRECIEVEERR:
      Serial.println("Communication error");
      return p;
    case FINGERPRINT_IMAGEFAIL:
      Serial.println("Imaging error");
      return p;
    default:
      Serial.println("Unknown error");
      return p;
    }
  }

  // Convert image to feature template 1
  p = finger.image2Tz(1);
  switch (p) {
    case FINGERPRINT_OK:
      Serial.println("Image converted");
      break;
    case FINGERPRINT_IMAGEMESS:
      Serial.println("Image too messy");
      return p;
    case FINGERPRINT_PACKETRECIEVEERR:
      Serial.println("Communication error");
      return p;
    case FINGERPRINT_FEATUREFAIL:
      Serial.println("Could not find fingerprint features");
      return p;
    case FINGERPRINT_INVALIDIMAGE:
      Serial.println("Could not find fingerprint features");
      return p;
    default:
      Serial.println("Unknown error");
      return p;
  }

  Serial.println("Remove finger");
  delay(2000);
  p = 0;
  // Wait for finger removal
  while (p != FINGERPRINT_NOFINGER) {
    p = finger.getImage();
    delay(100); // Add small delay
  }
  Serial.print("ID "); Serial.println(id);
  p = -1;
  Serial.println("Place same finger again");
  // Wait for finger again
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    switch (p) {
    case FINGERPRINT_OK:
      Serial.println("Image taken");
      break;
    case FINGERPRINT_NOFINGER:
      Serial.print(".");
      delay(100);
      break;
    case FINGERPRINT_PACKETRECIEVEERR:
      Serial.println("Communication error");
      return p;
    case FINGERPRINT_IMAGEFAIL:
      Serial.println("Imaging error");
      return p;
    default:
      Serial.println("Unknown error");
      return p;
    }
  }

  // Convert image to feature template 2
  p = finger.image2Tz(2);
  switch (p) {
    case FINGERPRINT_OK:
      Serial.println("Image converted");
      break;
    case FINGERPRINT_IMAGEMESS:
      Serial.println("Image too messy");
      return p;
    case FINGERPRINT_PACKETRECIEVEERR:
      Serial.println("Communication error");
      return p;
    case FINGERPRINT_FEATUREFAIL:
      Serial.println("Could not find fingerprint features");
      return p;
    case FINGERPRINT_INVALIDIMAGE:
      Serial.println("Could not find fingerprint features");
      return p;
    default:
      Serial.println("Unknown error");
      return p;
  }

  // Create model from the two templates
  Serial.print("Creating model for #"); Serial.println(id);
  p = finger.createModel();
  if (p == FINGERPRINT_OK) {
    Serial.println("Prints matched!");
  } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
    Serial.println("Communication error");
    return p;
  } else if (p == FINGERPRINT_ENROLLMISMATCH) {
    Serial.println("Fingerprints did not match");
    return p;
  } else {
    Serial.println("Unknown error");
    return p;
  }

  // Store the model
  Serial.print("Storing model #"); Serial.println(id);
  p = finger.storeModel(id);
  if (p == FINGERPRINT_OK) {
    Serial.println("Stored!");
    return FINGERPRINT_OK; // Explicitly return OK
  } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
    Serial.println("Communication error");
    return p;
  } else if (p == FINGERPRINT_BADLOCATION) {
    Serial.println("Could not store in that location");
    return p;
  } else if (p == FINGERPRINT_FLASHERR) {
    Serial.println("Error writing to flash");
    return p;
  } else {
    Serial.println("Unknown error");
    return p;
  }
}

// Returns Fingerprint ID#, -1 for no match, -2 for communication error, -3 for image error
int verifyFingerprint() {
  uint8_t p = finger.getImage();
  if (p != FINGERPRINT_OK) {
     if (p == FINGERPRINT_NOFINGER) {
        Serial.println("No finger detected");
        return -1; // Use -1 consistently for no match / no finger
     }
     if (p == FINGERPRINT_PACKETRECIEVEERR) {
        Serial.println("Communication error");
        return -2;
     }
     if (p == FINGERPRINT_IMAGEFAIL) {
         Serial.println("Imaging error");
         return -3;
     }
      Serial.println("Unknown image error");
      return -3; // General image error
  }

  p = finger.image2Tz();
   if (p != FINGERPRINT_OK) {
    if (p == FINGERPRINT_IMAGEMESS) {
        Serial.println("Image too messy"); return -3;
    } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
        Serial.println("Communication error"); return -2;
    } else if (p == FINGERPRINT_FEATUREFAIL || p == FINGERPRINT_INVALIDIMAGE) {
        Serial.println("Could not find fingerprint features"); return -3;
    } else {
        Serial.println("Unknown template error"); return -3;
    }
  }

  p = finger.fingerFastSearch();
  if (p != FINGERPRINT_OK) {
    if (p == FINGERPRINT_PACKETRECIEVEERR) {
        Serial.println("Communication error"); return -2;
    } else if (p == FINGERPRINT_NOTFOUND) {
         Serial.println("Did not find a match"); return -1; // No match
    }
     else {
        Serial.println("Unknown search error"); return -1; // Treat as no match
    }
  }

  // Found a match!
  Serial.print("Found ID #"); Serial.print(finger.fingerID);
  Serial.print(" with confidence "); Serial.println(finger.confidence);
  return finger.fingerID;
}

// Helper to print error messages
void printFingerprintError(uint8_t p) {
  switch (p) {
    case FINGERPRINT_PACKETRECIEVEERR: Serial.println("Communication error"); break;
    case FINGERPRINT_NOFINGER: Serial.println("No finger detected"); break;
    case FINGERPRINT_IMAGEFAIL: Serial.println("Imaging error"); break;
    case FINGERPRINT_IMAGEMESS: Serial.println("Image too messy"); break;
    case FINGERPRINT_FEATUREFAIL: Serial.println("Could not find fingerprint features"); break;
    case FINGERPRINT_INVALIDIMAGE: Serial.println("Invalid image"); break;
    case FINGERPRINT_ENROLLMISMATCH: Serial.println("Fingerprints did not match"); break;
    case FINGERPRINT_BADLOCATION: Serial.println("Could not store in that location"); break;
    case FINGERPRINT_FLASHERR: Serial.println("Error writing to flash"); break;
    default: Serial.printf("Unknown error: 0x%X\n", p); break;
  }
}

// URL Encode helper
String urlencode(String str)
{
    String encodedString="";
    char c;
    char code0;
    char code1;
    for (int i =0; i < str.length(); i++){
      c=str.charAt(i);
      if (c == ' '){
        encodedString+= '+';
      } else if (isalnum(c)){
        encodedString+=c;
      } else{
        code1=(c & 0xf)+'0';
        if ((c & 0xf) > 9){
            code1=(c & 0xf) - 10 + 'A';
        }
        c=(c>>4)&0xf;
        code0=c+'0';
        if (c > 9){
            code0=c - 10 + 'A';
        }
        encodedString+='%';
        encodedString+=code0;
        encodedString+=code1;
      }
      yield(); // Prevent watchdog timeout for long strings
    }
    return encodedString;
}