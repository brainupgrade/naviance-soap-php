# naviance-soap-php
A PHP solution to Naviance Single-Sign-On (a SOAP server emulator)

Requires: https://github.com/Rich2k/adLDAP

Edit the naviance.wsdl file.  Change "server.mydomain.com" to your own server, and edit line 65 (the soap:address); it needs to be changed to the path (URI) where you install this script.

You will also need to configure adLDAP to access your Active Directory server.

The SSO settings in Naviance Setup require two parameter: 
1) Delegated Gateway URL: ... example: https://server.mydomain.com/soap/naviance_auth.php?wsdl (must have ?wsdl at the end)
2) Secure Passphrase: ... example: yoursecretpassphrase 

What this script does is 1) deliver a formated XML file (the WSDL file) to Naviance upon request, 2) parse the authentication request (the XML payload) from Naviance to extract the username and password, 3) call LDAP server to verify the credentials, and 4) return the expected XML file to Naviance with the apropriate success/fail message.

