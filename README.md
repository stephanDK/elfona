# elfona
_Showing private Danish electricity consumer data on an ePaper display using a php-page and an Arduino._

## About the project
Just recently, Danish electricity consumers got the possibility to acces their own private consumption (and production) data via an API-call to the web-portal eloverblik.dk. This project aims to demonstrate how these data can be accessed and viewed in an easy and meaningful way. Currently the consumption for the day before is shown, together with the average consumption (pr. day) of the month before and of the year before.
As hardware, I used an Arduino MKR WiFi 1010, which can go directly on a wifi-network without any additional shield. An ePaper display is what is used on e.g. eBook readers like Kindle. It consumes only power when in use (on page changes) and it does have a very high contrast and can be easily read even in sunlight. The Waveshare ePaper display can directly be coupled together with a 3.3V Arduino board. 

## A few words about me
sdfsdf

## Links
* https://eloverblik.dk/welcome
* https://energinet.dk/El/Elmarkedet/MDA---Ny-loesning (containing a guideline for the API)
* https://www.arduino.cc/en/Guide/MKRWiFi1010
* https://www.waveshare.com/wiki/4.2inch_e-Paper_Module
