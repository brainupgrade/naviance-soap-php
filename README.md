# naviance-soap-php
A PHP solution to Naviance Single-Sign-On

Requires: https://github.com/Rich2k/adLDAP

Edit the naviance.wsdl file.  Change "server.mydomain.com" to your own server, and edit line 65 (the soap:address); it needs to be changed to the path (URI) where you install this script.

You will also need to configure adLDAP to access your Active Directory server.

What this script does is 1) deliver a formated XML file (the WSDL file) to Naviance upon request, 2) parse the authentication request (the XML payload) from Naviance to extract the username and password, 3) call LDAP server to verify the credentials, and 4) return the expected XML file to Naviance with the apropriate success/fail message.
