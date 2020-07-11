# elfona
_"ELForbrug ON Arduino" - Showing private Danish electricity consumer data on an ePaper display using an Arduino and an ePaper display._

## About the project
Just recently, Danish electricity consumers got the possibility to acces their own private consumption (and production) data via an API-call to the web-portal eloverblik.dk. This project aims to demonstrate how these data can be accessed and viewed in an easy and meaningful way. Currently the consumption for the day before is shown, together with the average consumption (pr. day) of the month before and of the year before. 

The data portal eloverblik.dk allows Danish electricity consumers to access their own electricity meter data via a refresh-token. This token is valid for one hour and can be retrieved by a webrequest with another, personal token. This personal token is necessary for the solution to work! It can be created when logged into eloverblik.dk:
- Login at https://eloverblik.dk/Customer/login
- Click on the little man at the upper right side and choose "Datadeling"
- "Opret Token" and copy it for the usage in the program code
By the way, if you cannot see at least one "Målepunkt" (measurement point) in the overview, you need to add one first. For this, you need your measurementPoint Id and a web-accesscode. You might be able to find these on your electricity bill but I had to call to my grid company (netselskab) to get at least the code.

As hardware, I decided to use an Arduino MKR WiFi 1010, which can go directly on a wifi-network without any additional shield. An ePaper display is what is used on e.g. eBook readers like the Kindle. It consumes only power when in use (on page changes), it does have a very high contrast and can easily be read even in sunlight. The Waveshare ePaper display can directly be coupled together with a 3.3V Arduino board. 

I decided not to directly connect the Arduino to eloverblik.dk but instead to put a php-site inbetween. While it would be possible to do it, some reasons for adding a php-site inbetween are:
- better scalability of the solution (e.g. adding other sources in later releases as well)
- more easy work-sharing (working on .php and .ino files in parallel)
- php-site can be tested independently, also without any Arduino
- php-site can be used by other platforms/projects
Of course, the drawback of this decision is, that you need to have a webserver or cloud-solution, where you can upload and run the php-site from.

## Links
* https://eloverblik.dk/welcome
* https://energinet.dk/El/Elmarkedet/MDA---Ny-loesning (containing a guideline for the API)
* https://www.arduino.cc/en/Guide/MKRWiFi1010
* https://www.waveshare.com/wiki/4.2inch_e-Paper_Module
* https://github.com/stephanDK/elfona
