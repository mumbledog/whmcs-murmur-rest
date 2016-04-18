# WHMCS-Murmur-REST

A WHMCS module for provisioning and configuring Mumble VOIP servers using Murmur-REST.

## Configuration

* Clone the repository and copy the relevant files to your WHMCS directory so the modules end up in the right place (whmcsdir/modules/servers/murmurrest).
* Add a new server with the MurmurREST module:
  * Set hostname to be the hostname of the REST endpoint where Murmur-REST lives (at the moment MurmurREST *must* be at the root of the HTTP vhost, it can't be in a subdirectory)
  * Set IP address to be the IP address that Murmurs on this server will bind to.
  * Username/password will be a username and password configured in Murmur-REST's settings.py.
  * Tick secure if using HTTPS (**highly recommended**, see "Security" below) - this requires SSL bits for php-curl.
  * Set the port if Murmur-REST isn't listening on 80 or 443.
  * After saving your settings, go back into the server configuration, and click "test connection" and it should show "SUCCESSFUL" within a couple of seconds.
* Now create a new product using that server - configure the slots (number of users) accordingly.
* Repeat process for additional products and servers.
* Place a test order to see if provisioning works as you expect.

## When a server is provisioned

* A server will be created on an available server for the product per WHMCS provisioning rules (least empty server, etc).
* It's IP:Port will be copied into the "Dedicated IP" field of the service (host= must be set correctly in each Murmur's .ini file for this to work)
* The auto-generated password in WHMCS will be set as the superuser password.
* The server will be started.
* You can then use a custom Welcome email to send the dedicatedip and password, along with the URL to your control panel to the customer.

## Security

Out of the box, Murmur-REST ships using Digest authentication and Flask's cookie sessions. If you're not using HTTPS, and Murmur-REST and all it's authenticated clients don't live on the same host, then Digest auth is vulnerable to replaying of the credentials.

**It's strongly recommended that all authenticated calls to Murmur-REST go over HTTPS.**
