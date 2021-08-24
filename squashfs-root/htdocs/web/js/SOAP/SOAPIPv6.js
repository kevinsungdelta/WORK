/**
 * @constructor
 */
function SOAPIPv6_Address()
{
	this.string = new Array();
}


/**
 * @constructor
 */
function SOAPGetIPv6StatusResponse()
{
	var string = "";

	this.IPv6_ConnectionType = "";
	this.IPv6_Network_Status = "";
	this.IPv6_ConnectionTime = 0;
	this.IPv6_WanAddress = new SOAPIPv6_Address();
	this.IPv6_WanLinkLocalAddress = "";
	this.IPv6_DefaultGateway = "";
	this.IPv6_LanAddress = new SOAPIPv6_Address();
	this.IPv6_LanLinkLocalAddress = "";
	this.IPv6_PrimaryDNS = "";
	this.IPv6_SecondaryDNS = "";
	this.IPv6_DhcpPd = "";
	this.IPv6_DhcpPdPrefix = new SOAPIPv6_Address();
	this.IPv6_GuestZoneAddress = new SOAPIPv6_Address();
	this.IPv6_GuestZoneLinkLocalAddress = "";

};

// @prototype
SOAPGetIPv6StatusResponse.prototype = 
{

}

/**
 * @constructor
 */
function SOAPRenewIPv6WanConnection()
{
	this.Action = "";
}
