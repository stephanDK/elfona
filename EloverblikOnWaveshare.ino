// File: EloverblikOnWaveshare.ino
// Version: 0.2.0 Extended with reading, handling and displaying CO2 prognosis. Minor other corrections
// Version: 0.1.0 Initial Version
// Location: https://github.com/stephanDK/elfona
// This file is part of the elfona-project which extracts DK electricity consumer data and prepares them to be shown e.g. on an ePaper display managed by an Arduino.

#include <SPI.h>
#include <WiFiNINA.h>
#include "epd4in2.h"
#include "epdpaint.h"

#define COLORED     0
#define UNCOLORED   1

// Wiring
// Waveshare - Color - Arduino
// BUSY - lila - -5
// RST - white - 6
// DC - green - 7
// CS- orange - -4
// CLK - yellow - 9
// DIN - blue - 8
// GND - black - GND
// VCC - red - VCC

// Put your own data below:
const char server[] = "???"; // ACTION: Replace with the server name where your php-file is located (e.g. www.myserver.dk)
const char fileNameElConsumption[] = "???"; // ACTION: Replace with the file location on the server (e.g. "/el.php")
const char fileNameCo2Prognosis[] = "???"; // ACTION: Replace with the file location on the server (e.g. "/co2.php")
char ssid[] = "???"; // ACTION: Replace with your wifi network SSID
char pass[] = "???"; // ACTION: Replace with your wifi network password

WiFiClient client;
int status = WL_IDLE_STATUS;  // the Wifi radio's status
const int margin = 10;
const long updateFrequency = 1*60*60*1000; // Update ever 1 hour(s)
String elConsumptionStr = "";
String co2PrognosisStr = "";

// *********** SETUP and LOOP ***********
void setup()
{
  pinMode(LED_BUILTIN, OUTPUT);
}

void loop() // main loop - update display and sleep
{
  while(GetDataAndUpdateDisplay() == false)
  {
    Serial.println("ePaper update not possible, waiting 5min... before retry");
    delay(5*60*1000); // Update was not possible? Retry after 5min!
  }
  digitalWrite(LED_BUILTIN, LOW);
  delay(updateFrequency);
}

// ***** Wifi Methods *****
void SetupWifi()
{
  // attempt to connect to Wifi network:
  status = WiFi.begin(ssid, pass);
  for(int i=0; i<5; i++) // 10sec should be enough to connect to network
  {
    digitalWrite(LED_BUILTIN, HIGH);
    delay(1000);
    digitalWrite(LED_BUILTIN, LOW);
    delay(1000);
  }

  if(status != WL_CONNECTED) // not connected? blink fast 10 times 
  {
    for(int i=0; i<10; ++i)
    {
      digitalWrite(LED_BUILTIN, HIGH);
      delay(100);
      digitalWrite(LED_BUILTIN, LOW);
      delay(200);
    }
  }
  
  if(status != WL_CONNECTED) // still not connected? ty again
    SetupWifi();
}

void ShutdownWifi() // shutdown = save power
{
  client.stop(); //stop client
  WiFi.end();
  status = WL_IDLE_STATUS;
}

// *********** Web retrieval methods ***********
String RetrieveData(const char fileName[])
{
  String output = "";
  if (client.connect(server, 80))
  {
     client.print("GET ");
     client.print(fileName);
     client.println(" HTTP/1.1");
     client.print("Host: ");
     client.println(server);
     client.println("User-Agent: Arduino");
     client.println("Accept: text/html");
     client.println("Connection: close");
     client.println();
  }

  while(client.connected() && !client.available())
    delay(1); //waits for data

  while (client.available())
    output += (char)client.read();

  return output;
}

String ParseWebString(String data, int itemNo) // String is delimited by "#" get item itemNo from the string data
{
  int firstpos=0;
  int nextpos = -1;

  for(int i=0; i< itemNo; ++i)
  {
    firstpos=data.indexOf('#',firstpos+1);
    if(firstpos == -1) { return "<EOF>"; }
  }
  nextpos=data.indexOf('#',firstpos+1);
  if(nextpos == -1) { nextpos = data.length()-1; }

  String subtext = data.substring(firstpos+1, nextpos); // +1 to get rid of the "#" character

  //  Serial.print("Parse: ");
  //  Serial.print(itemNo);
  //  Serial.print(": ");
  //  Serial.println(subtext);
  return subtext;
}
// *********** END Web retrieval methods ***********

// *********** Get data and display METHODS ***********
bool GetDataAndUpdateDisplay()
{
  SetupWifi();

  String tmpElConsumptionStr = RetrieveData(fileNameElConsumption);
  if(tmpElConsumptionStr.indexOf("#") > -1)
  {
    elConsumptionStr = tmpElConsumptionStr;
  }
  
  String tmpCo2PrognosisStr = RetrieveData(fileNameCo2Prognosis);
  if(tmpCo2PrognosisStr.indexOf("#") > -1)
  {
    co2PrognosisStr = tmpCo2PrognosisStr;
  }

  ShutdownWifi();
    
  Epd epd;
  if (epd.Init() != 0)
  {
    return false;
  }
  
  unsigned char image[1500];
  Paint paint(image, 400, 20);
  epd.ClearFrame(); // This clears the SRAM of the e-paper display

  UpdateElConsumptionWindow(epd, paint, elConsumptionStr);
  UpdateCo2PrognosisWindow(epd, paint, co2PrognosisStr);
  UpdateFooter(epd, paint, ParseWebString(elConsumptionStr, 1));
  
  epd.DisplayFrame(); // This displays the data from the SRAM in e-Paper module
  epd.Sleep(); // Deep sleep
  return true;
}

void UpdateElConsumptionWindow(Epd epd, Paint paint, String elConsumptionStr)
{
  char cText[40];
  int measures = ParseWebString(elConsumptionStr, 2).toInt(); // how many meters are there?

  // Draw display header
  paint.Clear(COLORED);
  paint.DrawStringAt(margin, 2, "Mit Eloverblik", &Font20, UNCOLORED);
  epd.SetPartialWindow(paint.GetImage(), 0, 0, paint.GetWidth(), paint.GetHeight());

  // Draw measure data (yesterday, avg last month and avg last year) for each meter
  for(int m=0; m<measures; m++) // if more than 2 meters, sizing and placement must be re-designed
  { 
    for(int i=0; i<4; i++)
    {
      paint.Clear(UNCOLORED);

      int index = 3+m*4+i;
      String sText = ParseWebString(elConsumptionStr, index);
      sText.toCharArray(cText, sizeof(cText));
      paint.DrawStringAt(margin, 2, cText, &Font16, COLORED);
      
      if(i==0)
        paint.DrawHorizontalLine(0, 0, 400, COLORED);
      if(i==0 || i==3)
        paint.DrawHorizontalLine(0, 19, 400, COLORED);
        
      paint.DrawVerticalLine(0, 0, 20, COLORED);
      paint.DrawVerticalLine(399, 0, 20, COLORED);
      
      epd.SetPartialWindow(paint.GetImage(), 0, 25+((5+80)*m)+(20*i), paint.GetWidth(), paint.GetHeight());
    }
  }
}

void UpdateCo2PrognosisWindow(Epd epd, Paint paint, String co2PrognosisStr)
{
  char cText[40];

  // Draw display header
  paint.Clear(UNCOLORED);
  paint.DrawStringAt(margin, 2, "CO2 emission prognosis (g/kWh)", &Font16, COLORED);
  paint.DrawRectangle(0, 0, 399, 19, COLORED);
  epd.SetPartialWindow(paint.GetImage(), 0, 195, paint.GetWidth(), paint.GetHeight());

  // Draw prognosis data (now + 8 hours)measure data (yesterday, avg last month and avg last year) for each meter
  for(int i=0; i<3; i++) // 3 rows
  { 
    paint.Clear(UNCOLORED);
    for(int j=0; j<3; j++) // 3 columns in each row
    {
      String sText = ParseWebString(co2PrognosisStr, 1+(i*3)+j);
      // Serial.print(i); Serial.print(", ");Serial.print(j); Serial.print(": "); Serial.println(sText);
      sText.toCharArray(cText, sizeof(cText));
      paint.DrawStringAt(margin+(133*j), 2, cText, &Font16, COLORED);
    }

    paint.DrawVerticalLine(0, 0, 20, COLORED);
    paint.DrawVerticalLine(399, 0, 20, COLORED);
    if(i==2)// draw bottom line
    {
      paint.DrawHorizontalLine(0, 19, 400, COLORED);
    }
    epd.SetPartialWindow(paint.GetImage(), 0, 215+(20*i), paint.GetWidth(), paint.GetHeight());
  }
}

void UpdateFooter(Epd epd, Paint paint, String timeStamp) // Draw footer line (showing last updated timestamp)
{
  paint.Clear(UNCOLORED);
  paint.DrawRectangle(0,0,399,19, COLORED);

  String sText = "Last updated: " + timeStamp;
  char cText[sText.length()+1];
  sText.toCharArray(cText, sizeof(cText));
  paint.DrawStringAt(180, 5, cText, &Font12, COLORED);

  epd.SetPartialWindow(paint.GetImage(), 0, 280, paint.GetWidth(), paint.GetHeight());
}
