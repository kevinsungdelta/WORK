HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8

<?
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";

include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/inet.php";
include "/htdocs/webinc/config.php";
include "/htdocs/phplib/encrypt.php";

$nodebase = "/runtime/hnap/SetWanSettings/";

$Type          = query($nodebase."Type");
$MacAddress    = query($nodebase."MacAddress");
$IPAddress     = query($nodebase."IPAddress");
$SubnetMask    = query($nodebase."SubnetMask");
$Gateway       = query($nodebase."Gateway");
$MTU           = query($nodebase."MTU");
$Username      = query($nodebase."Username");
$Password      = query($nodebase."Password");
$Password      = AES_Decrypt128($Password);
$MaxIdleTime   = query($nodebase."MaxIdleTime");
$MaxIdleTime   = $MaxIdleTime/60; //Alpha saves maximum idle time in DB with minute unit. However, HNAP SPEC. HNAP Core Specification v1.1 Rev12.pdf uses second for the unit.
$HostName      = query($nodebase."HostName");
$ServiceName   = query($nodebase."ServiceName");
$AutoReconnect = query($nodebase."AutoReconnect");
$PriDns        = query($nodebase."ConfigDNS/Primary");
$SecDns        = query($nodebase."ConfigDNS/Secondary");
$OpenDnsEnable = query($nodebase."OpenDNS/enable");
$DsLite_Configuration    = query($nodebase."DsLite_Configuration");
$DsLite_AFTR_IPv6Address = query($nodebase."DsLite_AFTR_IPv6Address");
$DsLite_B4IPv4Address    = query($nodebase."DsLite_B4IPv4Address");
$Wizard        = query($nodebase."Wizard"); //For dir880, WAN type DHCP <-> PPPoE would reboot. However, it could not reboot immediately for wizard settings to avoid skip mydlink settings. 

//For Russia
$Ruppp_Type          = query($nodebase."RussiaPPP/Type");
$Ruppp_IPAddress     = query($nodebase."RussiaPPP/IPAddress");
$Ruppp_SubnetMask    = query($nodebase."RussiaPPP/SubnetMask");
$Ruppp_Gateway       = query($nodebase."RussiaPPP/Gateway");
$Ruppp_PriDns        = query($nodebase."RussiaPPP/DNS/Primary");
$Ruppp_SecDns        = query($nodebase."RussiaPPP/DNS/Secondary");

$mac = "";
$rlt = "OK";


$path_inf_wan1 = XNODE_getpathbytarget("", "inf", "uid", $WAN1, 0);
$path_inf_wan2 = XNODE_getpathbytarget("", "inf", "uid", $WAN2, 0);
$path_inf_wan3 = XNODE_getpathbytarget("", "inf", "uid", $WAN3, 0);
$path_inf_wan4 = XNODE_getpathbytarget("", "inf", "uid", $WAN4, 0);
$path_inf_br1 = XNODE_getpathbytarget("", "inf", "uid", $BR1, 0);

$path_inf_lan1 = XNODE_getpathbytarget("", "inf", "uid", $LAN1, 0);
$path_inf_lan2 = XNODE_getpathbytarget("", "inf", "uid", $LAN2, 0);
$path_inf_lan3 = XNODE_getpathbytarget("", "inf", "uid", $LAN3, 0);
$path_inf_lan4 = XNODE_getpathbytarget("", "inf", "uid", $LAN4, 0);

$wan1_inet = query($path_inf_wan1."/inet"); 
$wan2_inet = query($path_inf_wan2."/inet");
$wan3_inet = query($path_inf_wan3."/inet");
$wan4_inet = query($path_inf_wan4."/inet");
$br1_inet = query($path_inf_br1."/inet");

$path_wan1_inet = XNODE_getpathbytarget("/inet", "entry", "uid", $wan1_inet, 0);
$path_wan2_inet = XNODE_getpathbytarget("/inet", "entry", "uid", $wan2_inet, 0); 
$path_wan3_inet = XNODE_getpathbytarget("/inet", "entry", "uid", $wan3_inet, 0);
$path_wan4_inet = XNODE_getpathbytarget("/inet", "entry", "uid", $wan4_inet, 0);
$path_br1_inet = XNODE_getpathbytarget("/inet", "entry", "uid", $br1_inet, 0);

$wan1_phyinf = query($path_inf_wan1."/phyinf");
$path_wan1_phyinf = XNODE_getpathbytarget("", "phyinf", "uid", $wan1_phyinf, 0);

$path_run_inf_wan1 = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN1, 0);

$current_layout = query("/device/layout");

if($HostName != "") set("/device/hostname_dhcpc", $HostName);

//Check the DNS address is valid or not.
if(INET_validv4addr($PriDns)!=1) $PriDns="";
if(INET_validv4addr($SecDns)!=1) $SecDns="";

//if( $PriDns != "" || $SecDns != "" )
//{
	if($OpenDnsEnable == "true")
	{
		set("/advdns/enable", 1);
	}
	else
	{
		set("/advdns/enable", 0);
	}
//}


//+++ Jerry Kao, add Clear node for Init WAN-1 and WAN-2.
set($path_inf_wan1."/active",     "1");
set($path_inf_wan1."/nat",    "NAT-1");		// DS-Lite mode will clear /nat node.
set($path_inf_wan1."/schedule",    "");
set($path_inf_wan1."/lowerlayer",  "");
set($path_inf_wan1."/upperlayer",  "");
set($path_inf_wan1."/infprevious", "");

set($path_wan1_inet."/ipv4/ipv4in6/mode", "");

set($path_inf_wan2."/active",     "0");
set($path_inf_wan2."/nat",         "");
set($path_inf_wan2."/schedule",    "");
set($path_inf_wan2."/lowerlayer",  "");
set($path_inf_wan2."/upperlayer",  "");


if(strstr($Type, "Bridged")!="")
{
	$layout = "bridge";
}
else
{
	$layout = "router";
}
set("/device/layout", $layout);

//For pppoe, we need to disable Broadcom hardware NAT. But Broadcom hardware NAT cannot be configured at runtime.
//We need to reboot device. (tom, 20140121)
$old_addrtype = get("x", $path_wan1_inet."/addrtype");

if($Type == "Static")
{	
	set($path_inf_wan1."/lowerlayer",   "");
	set($path_wan1_inet."/addrtype",    "ipv4");
	set($path_wan1_inet."/ipv4/static", "1");
	set($path_inf_wan2."/active",       "0");
	
	anchor($path_wan1_inet."/ipv4");
	//When the status of MAC clone is changed, reboot the device. The behavior is followed D-Link old GUI.
	$MacAddress_old = get("", $path_wan1_phyinf."/macaddr");
	if($MacAddress != $MacAddress_old)	{$rlt="REBOOT";}
	set($path_wan1_phyinf."/macaddr", $MacAddress);
	set("ipaddr", $IPAddress);
	set("mask", ipv4mask2int($SubnetMask));
	set("gateway", $Gateway);
	
	if($MTU == "0")
	{
		//$rlt = "ERROR_AUTO_MTU_NOT_SUPPORTED";
		set("mtu", 1500);
	}
	else
	{
		if($MTU >= 200 && $MTU <= 1500) { set("mtu", $MTU); }
		else	{ $rlt="ERROR"; }
	}
	
	//DNS
	if($PriDns != "" && $SecDns != "")			{ set("dns/count", "2");}
	else if($PriDns != "" || $SecDns != "")	{ set("dns/count", "1");}
	else																		{ set("dns/count", "0");}
	
	set("dns/entry", $PriDns); 
	set("dns/entry:2", $SecDns);
}
else if($Type == "DHCP")
{		
	set($path_inf_wan1."/lowerlayer",   "");
	set($path_wan1_inet."/addrtype",    "ipv4");
	set($path_wan1_inet."/ipv4/static", "0");
	set($path_inf_wan2."/active",       "0");
	
	anchor($path_wan1_inet."/ipv4");
	//When the status of MAC clone is changed, reboot the device. The behavior is followed D-Link old GUI.
	$MacAddress_old = get("", $path_wan1_phyinf."/macaddr");
	if($MacAddress != $MacAddress_old)	{$rlt="REBOOT";}
	set($path_wan1_phyinf."/macaddr", $MacAddress);
	
	if($MTU == "0")
	{
		//$rlt = "ERROR_AUTO_MTU_NOT_SUPPORTED";
		set("mtu", 1500);
	}
	else
	{
		if($MTU >= 200 && $MTU <= 1500) { set("mtu", $MTU); }
		else	{ $rlt="ERROR"; }
	}

	//DNS
	if($PriDns != "" && $SecDns != "")			{ set("dns/count", "2");}
	else if($PriDns != "" || $SecDns != "")	{ set("dns/count", "1");}
	else																		{ set("dns/count", "0");}
	
	set("dns/entry", $PriDns);
	set("dns/entry:2", $SecDns);
}
else if($Type == "StaticPPPoE" || $Type == "DHCPPPPoE")     //-----PPPoE
{
	set($path_wan1_inet."/addrtype",  "ppp4");
	set($path_wan1_inet."/ppp4/over", "eth");
	set($path_inf_wan2."/active",     "0");
	
	anchor($path_wan1_inet."/ppp4");
	
	if($Type == "StaticPPPoE")
	{
		set("static", 1);
		set("ipaddr", $IPAddress);
	}
	else
	{
		set("static", 0);
	}

	set("username", $Username);
	set("password", $Password);
	set("pppoe/servicename", $ServiceName);
	
	//Reconnect Mode
	set("dialup/idletimeout", $MaxIdleTime);
	if($AutoReconnect=="false")
	{
		if($MaxIdleTime>0) { set("dialup/mode", "ondemand");}
		else { set("dialup/mode", "manual");}
	}
	else { set("dialup/mode", "auto");}
	
	//When the status of MAC clone is changed, reboot the device. The behavior is followed D-Link old GUI.
	$MacAddress_old = get("", $path_wan1_phyinf."/macaddr");
	if($MacAddress != $MacAddress_old)	{$rlt="REBOOT";}
	set($path_wan1_phyinf."/macaddr", $MacAddress);
	
	if($MTU == "0")
	{
		//$rlt = "ERROR_AUTO_MTU_NOT_SUPPORTED";
		set("mtu", 1492);
	}
	else
	{
		if($MTU >= 200 && $MTU <= 1492) { set("mtu", $MTU); }
		else	{ $rlt="ERROR"; }
	}
	
	//DNS
	if($PriDns != "" && $SecDns != "")			{ set("dns/count", "2");}
	else if($PriDns != "" || $SecDns != "")	{ set("dns/count", "1");}
	else																		{ set("dns/count", "0");}
	
	set($path_run_inf_wan1."/inet/ppp4/dns",   $PriDns);
	set($path_run_inf_wan1."/inet/ppp4/dns:2", $SecDns); 
	
	set("dns/entry",   $PriDns);
	set("dns/entry:2", $SecDns);
}
else if($Type == "Ru_StaticPPPoE" || $Type == "Ru_DHCPPPPoE")     //----- Russia PPPoE
{
	set($path_wan1_inet."/addrtype",  "ppp4");
	set($path_wan1_inet."/ppp4/over", "eth");
	set($path_inf_wan2."/active",     "0");
	
	anchor($path_wan1_inet."/ppp4");
	
	if($Type == "Ru_StaticPPPoE")
	{
		set("static", 1);
		set("ipaddr", $IPAddress);
	}
	else
	{
		set("static", 0);
	}

	set("username", $Username);
	set("password", $Password);
	set("pppoe/servicename", $ServiceName);
	
	//Reconnect Mode
	set("dialup/idletimeout", $MaxIdleTime);
	if($AutoReconnect=="false")
	{
		if($MaxIdleTime>0) { set("dialup/mode", "ondemand");}
		else { set("dialup/mode", "manual");}
	}
	else { set("dialup/mode", "auto");}
	
	//When the status of MAC clone is changed, reboot the device. The behavior is followed D-Link old GUI.
	$MacAddress_old = get("", $path_wan1_phyinf."/macaddr");
	if($MacAddress != $MacAddress_old)	{$rlt="REBOOT";}
	set($path_wan1_phyinf."/macaddr", $MacAddress);
	
	if($MTU == "0")
	{
		//$rlt = "ERROR_AUTO_MTU_NOT_SUPPORTED";
		set("mtu", 1492);
	}
	else
	{
		if($MTU >= 200 && $MTU <= 1492) { set("mtu", $MTU); }
		else	{ $rlt="ERROR"; }
	}
	
	//DNS
	if($PriDns != "" && $SecDns != "")			{ set("dns/count", "2");}
	else if($PriDns != "" || $SecDns != "")	{ set("dns/count", "1");}
	else																		{ set("dns/count", "0");}
	
	set($path_run_inf_wan1."/inet/ppp4/dns",   $PriDns);
	set($path_run_inf_wan1."/inet/ppp4/dns:2", $SecDns); 
	
	set("dns/entry",   $PriDns);
	set("dns/entry:2", $SecDns);
	
	
	if($Ruppp_Type == "StaticPPPoE" || $Ruppp_Type == "DHCPPPPoE")     //----- Russia PPPoE WAN
	{	
		set($path_inf_wan2."/active",     "1");
		set($path_inf_wan2."/nat",    "NAT-1");
	
		anchor($path_wan2_inet."/ipv4");
		
		if($Ruppp_Type == "StaticPPPoE")
		{
			set("static", 1);
			set("ipaddr", $Ruppp_IPAddress);
			set("mask",   ipv4mask2int($Ruppp_SubnetMask));
			set("gateway", $Ruppp_Gateway); 
			
			if($Ruppp_PriDns != "")	
			{	
				if($Ruppp_PriDns != "" && $Ruppp_SecDns != "")	
				{ 
					set("dns/count", "2");
					set("dns/entry", $Ruppp_PriDns);
					set("dns/entry:2", $Ruppp_SecDns);
				}
				else
				{
					set("dns/count", "1");
					set("dns/entry", $Ruppp_PriDns);
					set("dns/entry:2", "");
				}
			}
		}
		else //Russia DynamicPPPoE
		{
			set("static",   0);
			set("ipaddr",  "");
			set("mask",    "");
			set("gateway", ""); 
			
			set("dns/count", "0");
			set("dns/entry", "");
			set("dns/entry:2", "");
		}
	}
}
else if($Type == "StaticPPTP" || $Type == "DynamicPPTP")     //-----PPTP
{
	set($path_inf_wan1."/lowerlayer", "WAN-2");	
	set($path_wan1_inet."/addrtype",  "ppp4");
	set($path_wan1_inet."/ppp4/over", "pptp");
	set($path_wan1_inet."/ppp4/static", "0");
		
	set($path_inf_wan2."/active",     "1");
	set($path_inf_wan2."/upperlayer", "WAN-1");
	set($path_inf_wan2."/nat",        "");
	
	set($path_inf_wan1."/defaultroute", "100");	
	set($path_inf_wan2."/defaultroute", "");	
	
	set($path_wan1_inet."/ppp4/username", $Username);
	set($path_wan1_inet."/ppp4/password", $Password);
	set($path_wan1_inet."/ppp4/pptp/server", $ServiceName);
	
	anchor($path_wan1_inet."/ppp4");
	
	if($Type == "StaticPPTP")
	{
		set($path_wan2_inet."/ipv4/static", 1);
		set($path_wan2_inet."/ipv4/ipaddr", $IPAddress);
		set($path_wan2_inet."/ipv4/mask",   ipv4mask2int($SubnetMask));
		set($path_wan2_inet."/ipv4/gateway", $Gateway); 
		
		if($PriDns != "")	
		{	
			if($PriDns != "" && $SecDns != "")	
			{ 
				set("dns/count", "2");
				set("dns/entry", $PriDns);
				set("dns/entry:2", $SecDns);
				set($path_wan2_inet."/ipv4/dns/count", "2");
				set($path_wan2_inet."/ipv4/dns/entry", $PriDns);
				set($path_wan2_inet."/ipv4/dns/entry:2", $SecDns);
			}
			else
			{
				set("dns/count", "1");
				set("dns/entry", $PriDns);
				set("dns/entry:2", "");
				set($path_wan2_inet."/ipv4/dns/count", "1");
				set($path_wan2_inet."/ipv4/dns/entry", $PriDns);
				set($path_wan2_inet."/ipv4/dns/entry:2", "");
			}
		}
	}
	else //DynamicPPTP
	{
		set($path_wan2_inet."/ipv4/static", 0);

		//DNS
		if($PriDns != "" && $SecDns != "")			{ set("dns/count", "2");}
		else if($PriDns != "" || $SecDns != "")	{ set("dns/count", "1");}
		else																		{ set("dns/count", "0");}

		set($path_run_inf_wan1."/inet/ppp4/dns",   $PriDns);
		set($path_run_inf_wan1."/inet/ppp4/dns:2", $SecDns);

		set("dns/entry",   $PriDns);
		set("dns/entry:2", $SecDns);

		set($path_wan2_inet."/ipv4/dns/count", "0");
	}
	
	//Reconnect Mode
	set("dialup/idletimeout", $MaxIdleTime);
	
	if($AutoReconnect=="false")
	{
		if($MaxIdleTime>0) { set("dialup/mode", "ondemand");}
		else { set("dialup/mode", "manual");}
	}
	else { set("dialup/mode", "auto");}
	
	//When the status of MAC clone is changed, reboot the device. The behavior is followed D-Link old GUI.
	$MacAddress_old = get("", $path_wan1_phyinf."/macaddr");
	if($MacAddress != $MacAddress_old)	{$rlt="REBOOT";}
	set($path_wan1_phyinf."/macaddr", $MacAddress);
	
	if($MTU == "0")
	{
		//$rlt = "ERROR_AUTO_MTU_NOT_SUPPORTED";
		set("mtu", 1400);
	}
	else
	{
		if($MTU >= 200 && $MTU <= 1460) { set("mtu", $MTU); }
		else	{ $rlt="ERROR"; }
	}
}	
else if($Type == "Ru_StaticPPTP" || $Type == "Ru_DynamicPPTP")     //----- Russia PPTP
{
	set($path_inf_wan1."/defaultroute", "100");	
	set($path_inf_wan2."/defaultroute", "200");	
	set($path_inf_wan1."/lowerlayer", "WAN-2");	
	set($path_inf_wan2."/upperlayer", "WAN-1");	
	
	set($path_inf_wan2."/active",     "1");
	set($path_inf_wan2."/nat",    "NAT-1");
	set($path_wan1_inet."/addrtype",  "ppp4");
	set($path_wan1_inet."/ppp4/over", "pptp");
	set($path_wan1_inet."/ppp4/static", "0");	
	
	set($path_wan1_inet."/ppp4/username", $Username);
	set($path_wan1_inet."/ppp4/password", $Password);
	set($path_wan1_inet."/ppp4/pptp/server", $ServiceName);
	
	anchor($path_wan1_inet."/ppp4");
	
	if($Type == "Ru_StaticPPTP")
	{
		set($path_wan2_inet."/ipv4/static", 1);
		set($path_wan2_inet."/ipv4/ipaddr", $IPAddress);
		set($path_wan2_inet."/ipv4/mask",   ipv4mask2int($SubnetMask));
		set($path_wan2_inet."/ipv4/gateway", $Gateway); 
		
		if($PriDns != "")	
		{	
			if($PriDns != "" && $SecDns != "")	
			{ 
				set("dns/count", "2");
				set("dns/entry", $PriDns);
				set("dns/entry:2", $SecDns);
				set($path_wan2_inet."/ipv4/dns/count", "2");
				set($path_wan2_inet."/ipv4/dns/entry", $PriDns);
				set($path_wan2_inet."/ipv4/dns/entry:2", $SecDns);
			}
			else
			{
				set("dns/count", "1");
				set("dns/entry", $PriDns);
				set("dns/entry:2", "");
				set($path_wan2_inet."/ipv4/dns/count", "1");
				set($path_wan2_inet."/ipv4/dns/entry", $PriDns);
				set($path_wan2_inet."/ipv4/dns/entry:2", "");
			}
		}
	}
	else //Russia DynamicPPTP
	{
		set($path_wan2_inet."/ipv4/static", 0);

		//DNS
		if($PriDns != "" && $SecDns != "")			{ set("dns/count", "2");}
		else if($PriDns != "" || $SecDns != "")	{ set("dns/count", "1");}
		else																		{ set("dns/count", "0");}

		set($path_run_inf_wan1."/inet/ppp4/dns",   $PriDns);
		set($path_run_inf_wan1."/inet/ppp4/dns:2", $SecDns);

		set("dns/entry",   $PriDns);
		set("dns/entry:2", $SecDns);

		set($path_wan2_inet."/ipv4/dns/count", "0");
	}
	
	//Reconnect Mode
	set("dialup/idletimeout", $MaxIdleTime);
	
	if($AutoReconnect=="false")
	{
		if($MaxIdleTime>0) { set("dialup/mode", "ondemand");}
		else { set("dialup/mode", "manual");}
	}
	else { set("dialup/mode", "auto");}
	
	//When the status of MAC clone is changed, reboot the device. The behavior is followed D-Link old GUI.
	$MacAddress_old = get("", $path_wan1_phyinf."/macaddr");
	if($MacAddress != $MacAddress_old)	{$rlt="REBOOT";}
	set($path_wan1_phyinf."/macaddr", $MacAddress);
	
	if($MTU == "0")
	{
		//$rlt = "ERROR_AUTO_MTU_NOT_SUPPORTED";
		set("mtu", 1400);
	}
	else
	{
		if($MTU >= 200 && $MTU <= 1460) { set("mtu", $MTU); }
		else	{ $rlt="ERROR"; }
	}
}	
else if($Type == "StaticL2TP" || $Type == "DynamicL2TP")     //-----L2TP
{
	set($path_inf_wan1."/lowerlayer", "WAN-2");	
	set($path_wan1_inet."/addrtype",  "ppp4");
	set($path_wan1_inet."/ppp4/over", "l2tp");
	set($path_wan1_inet."/ppp4/static", "0");
	
	set($path_inf_wan2."/active",     "1");
	set($path_inf_wan2."/upperlayer", "WAN-1");	
	set($path_inf_wan2."/nat",        "");
	
	set($path_inf_wan1."/defaultroute", "100");
	set($path_inf_wan2."/defaultroute", "");
	
	set($path_wan1_inet."/ppp4/l2tp/server", $ServiceName);
	set($path_wan1_inet."/ppp4/username", $Username);
	set($path_wan1_inet."/ppp4/password", $Password);
	
	anchor($path_wan1_inet."/ppp4");
	
	if($Type == "StaticL2TP")
	{
		set($path_wan2_inet."/ipv4/static", 1);
		set($path_wan2_inet."/ipv4/ipaddr", $IPAddress);
		set($path_wan2_inet."/ipv4/mask", ipv4mask2int($SubnetMask));
		set($path_wan2_inet."/ipv4/gateway", $Gateway);
		
		if($PriDns != "" || $SecDns != "")	
		{	
			if($PriDns != "" && $SecDns != "")	
			{ 
				set("dns/count", "2");
				set("dns/entry", $PriDns);
				set("dns/entry:2", $SecDns);
				set($path_wan2_inet."/ipv4/dns/count", "2");
				set($path_wan2_inet."/ipv4/dns/entry", $PriDns);
				set($path_wan2_inet."/ipv4/dns/entry:2", $SecDns);
			}
			else
			{
				set("dns/count", "1");
				set("dns/entry", $PriDns);
				set("dns/entry:2", "");
				set($path_wan2_inet."/ipv4/dns/count", "1");
				set($path_wan2_inet."/ipv4/dns/entry", $PriDns);
				set($path_wan2_inet."/ipv4/dns/entry:2", "");
			}
		}		
	}
	else //DynamicL2TP
	{
		set($path_wan2_inet."/ipv4/static", 0);
		
		//DNS
		if($PriDns != "" && $SecDns != "")			{ set("dns/count", "2");}
		else if($PriDns != "" || $SecDns != "")	{ set("dns/count", "1");}
		else																		{ set("dns/count", "0");}

		set($path_run_inf_wan1."/inet/ppp4/dns",   $PriDns);
		set($path_run_inf_wan1."/inet/ppp4/dns:2", $SecDns);

		set("dns/entry",   $PriDns);
		set("dns/entry:2", $SecDns);

		set($path_wan2_inet."/ipv4/dns/count", "0");
	}
	
	//Reconnect Mode
	set("dialup/idletimeout", $MaxIdleTime);
	if($AutoReconnect=="false")
	{
		if($MaxIdleTime>0) { set("dialup/mode", "ondemand");}
		else { set("dialup/mode", "manual");}
	}
	else { set("dialup/mode", "auto");}
	
	//When the status of MAC clone is changed, reboot the device. The behavior is followed D-Link old GUI.
	$MacAddress_old = get("", $path_wan1_phyinf."/macaddr");
	if($MacAddress != $MacAddress_old)	{$rlt="REBOOT";}
	set($path_wan1_phyinf."/macaddr", $MacAddress);
	
	if($MTU == "0")
	{
		//$rlt = "ERROR_AUTO_MTU_NOT_SUPPORTED";
		set("mtu", 1400);
	}
	else
	{
		if($MTU >= 200 && $MTU <= 1460) { set("mtu", $MTU); }
		else	{ $rlt="ERROR"; }
	}
}
else if($Type == "Ru_StaticL2TP" || $Type == "Ru_DynamicL2TP")     //----- Russia L2TP
{
	set($path_inf_wan1."/defaultroute", "100");
	set($path_inf_wan2."/defaultroute", "200");
	set($path_inf_wan1."/lowerlayer", "WAN-2");		
	set($path_inf_wan2."/upperlayer", "WAN-1");		
	
	set($path_inf_wan2."/active",     "1");	
	set($path_inf_wan2."/nat",    "NAT-1");	
	set($path_wan1_inet."/addrtype",  "ppp4");
	set($path_wan1_inet."/ppp4/over", "l2tp");	
	set($path_wan1_inet."/ppp4/static", "0");
	
	set($path_wan1_inet."/ppp4/l2tp/server", $ServiceName);
	set($path_wan1_inet."/ppp4/username", $Username);
	set($path_wan1_inet."/ppp4/password", $Password);
	
	anchor($path_wan1_inet."/ppp4");
	
	if($Type == "Ru_StaticL2TP")
	{
		set($path_wan2_inet."/ipv4/static", 1);
		set($path_wan2_inet."/ipv4/ipaddr", $IPAddress);
		set($path_wan2_inet."/ipv4/mask", ipv4mask2int($SubnetMask));
		set($path_wan2_inet."/ipv4/gateway", $Gateway);
		
		if($PriDns != "" || $SecDns != "")	
		{	
			if($PriDns != "" && $SecDns != "")	
			{ 
				set("dns/count", "2");
				set("dns/entry", $PriDns);
				set("dns/entry:2", $SecDns);
				set($path_wan2_inet."/ipv4/dns/count", "2");
				set($path_wan2_inet."/ipv4/dns/entry", $PriDns);
				set($path_wan2_inet."/ipv4/dns/entry:2", $SecDns);
			}
			else
			{
				set("dns/count", "1");
				set("dns/entry", $PriDns);
				set("dns/entry:2", "");
				set($path_wan2_inet."/ipv4/dns/count", "1");
				set($path_wan2_inet."/ipv4/dns/entry", $PriDns);
				set($path_wan2_inet."/ipv4/dns/entry:2", "");
			}
		}		
	}
	else //Russia DynamicL2TP
	{
		set($path_wan2_inet."/ipv4/static", 0);
		
		//DNS
		if($PriDns != "" && $SecDns != "")			{ set("dns/count", "2");}
		else if($PriDns != "" || $SecDns != "")	{ set("dns/count", "1");}
		else																		{ set("dns/count", "0");}

		set($path_run_inf_wan1."/inet/ppp4/dns",   $PriDns);
		set($path_run_inf_wan1."/inet/ppp4/dns:2", $SecDns);

		set("dns/entry",   $PriDns);
		set("dns/entry:2", $SecDns);

		set($path_wan2_inet."/ipv4/dns/count", "0");
	}
		
	//Reconnect Mode
	set("dialup/idletimeout", $MaxIdleTime);
	if($AutoReconnect=="false")
	{
		if($MaxIdleTime>0) { set("dialup/mode", "ondemand");}
		else { set("dialup/mode", "manual");}
	}
	else { set("dialup/mode", "auto");}
	
	//When the status of MAC clone is changed, reboot the device. The behavior is followed D-Link old GUI.
	$MacAddress_old = get("", $path_wan1_phyinf."/macaddr");
	if($MacAddress != $MacAddress_old)	{$rlt="REBOOT";}
	set($path_wan1_phyinf."/macaddr", $MacAddress);
	
	if($MTU == "0")
	{
		//$rlt = "ERROR_AUTO_MTU_NOT_SUPPORTED";
		set("mtu", 1400);
	}
	else
	{
		if($MTU >= 200 && $MTU <= 1460) { set("mtu", $MTU); }
		else	{ $rlt="ERROR"; }
	}
}
else if($Type == "DsLite")     //-----DS-Lite
{
	$addrtype_wan1 = query($path_wan1_inet."/addrtype");
	$addrtype_wan3 = query($path_wan3_inet."/addrtype");

	if($addrtype_wan1 == "ppp10" || $addrtype_wan3 == "ppp6")
	{
		//ppp6 or ppp10
		if($addrtype_wan3 == "ppp6")
		{
			set($path_inf_wan1."/infprevious", "WAN-4");
			set($path_inf_wan4."/infnext",     "WAN-1");
			set($path_inf_lan4."/dns6",        "DNS6-1");
			set($path_inf_lan4."/dnsrelay",    "1");
		}	
		else
		{
			//ppp10
			//never happen
		}
	}
	else
	{
		$wanmode6   = query($path_wan4_inet."/ipv6/mode");
		$wanactive6 = query($path_inf_wan4."/active");
		
		if($wanmode6 !="" && $wanactive6 !="0") 
		{
			if($wanmode6=="6TO4" || $wanmode6=="6RD" || $wanmode6=="6IN4")
			{
				$rlt = "ERROR_BAD_WANIPV6TYPE";
			}
			set($path_inf_wan1."/infprevious", "WAN-4");
			set($path_inf_wan4."/infnext", "WAN-1");
			if($wanmode6=="STATIC")	set($path_inf_wan4."/infnext:2", "LAN-4");

			/* enable dns proxy */
			set($path_inf_lan4."/dns6", "DNS6-1");
			set($path_inf_lan4."/dnsrelay", "1");
		}
		else
		{
			//LL
			$rlt = "ERROR_BAD_WANIPV6TYPE";
		}
	}
	if($DsLite_B4IPv4Address!="")
	{
		set($path_wan1_inet."/ipv4/ipaddr",	$DsLite_B4IPv4Address);
	}
	else
	{
		set($path_wan1_inet."/ipv4/ipaddr",	"");
	}
		
	set($path_inf_wan1."/nat",					"");
	set($path_wan1_inet."/addrtype",			"ipv4");
	set($path_wan1_inet."/ipv4/static",			"0");
	set($path_wan1_inet."/ipv4/ipv4in6/mode",	"dslite");
	set($path_wan1_inet."/ipv4/mtu",			"1452");

	if ($DsLite_Configuration == "Manual")
	{
		set($path_wan1_inet."/ipv4/ipv4in6/remote", $DsLite_AFTR_IPv6Address);
		set($path_inf_wan4."/dhcpc6",	"0");
	}
	else
	{
		set($path_wan1_inet."/ipv4/ipv4in6/remote", "");

		/* check ipv6 wan mode to decide if we need to do dhcpv6 info req */
		if($addrtype_wan3 != "ppp6")
		{
			$wanmode6 = query($path_wan4_inet."/ipv6/mode");
			if($wanmode6=="STATIC") set($path_inf_wan4."/dhcpc6",	"1");
		}
		else
		{
			//ppp6 && no pd && no dns
			//because we don't have static ppp6, so we don't do that?
		}
		
	}
}
else if($Type == "StaticBridged")     //-----Bridge mode Static
{
	set($path_br1_inet."/addrtype", "ipv4");
	
	anchor($path_br1_inet."/ipv4");
	set("static", "1");
	set("ipaddr", $IPAddress);
	set("mask", ipv4mask2int($SubnetMask));
	set("gateway", $Gateway);
	
	if($MTU == "0")
	{
		//$rlt = "ERROR_AUTO_MTU_NOT_SUPPORTED";
		set("mtu", 1500);
	}
	else
	{
		if($MTU >= 200 && $MTU <= 1500) { set("mtu", $MTU); }
		else	{ $rlt="ERROR"; }
	}
	
	//DNS
	if($PriDns != "" && $SecDns != "")			{ set("dns/count", "2");}
	else if($PriDns != "" || $SecDns != "")	{ set("dns/count", "1");}
	else																		{ set("dns/count", "0");}
	
	set("dns/entry", $PriDns); 
	set("dns/entry:2", $SecDns);
}
else if ($Type == "DynamicBridged")     //-----Bridge mode DHCP
{
	set($path_br1_inet."/addrtype", "ipv4");
	set($path_br1_inet."/ipv4/static", "0");
	
	anchor($path_br1_inet."/ipv4");
	if($MTU == "0")
	{
		//$rlt = "ERROR_AUTO_MTU_NOT_SUPPORTED";
		set("mtu", 1500);
	}
	else
	{
		if($MTU >= 200 && $MTU <= 1500) { set("mtu", $MTU); }
		else	{ $rlt="ERROR"; }
	}

	//DNS
	if($PriDns != "" && $SecDns != "")			{ set("dns/count", "2");}
	else if($PriDns != "" || $SecDns != "")	{ set("dns/count", "1");}
	else																		{ set("dns/count", "0");}
	
	set("dns/entry", $PriDns);
	set("dns/entry:2", $SecDns);
}
else
{
	$rlt = "ERROR_BAD_WANTYPE";
}

//For pppoe, we need to disable Broadcom hardware NAT. But Broadcom hardware NAT cannot be configured at runtime.
//We need to reboot device. (tom, 20140121)
if($rlt == "OK")
{
	$new_addrtype = get("x", $path_wan1_inet."/addrtype");
	if($new_addrtype != $old_addrtype)
	{
		//from ppp4 to other
		if($old_addrtype == "ppp4")
		{	$rlt = "REBOOT";}

		//from other to ppp4
		if($new_addrtype == "ppp4")
		{	$rlt = "REBOOT";}
	}
}

if($current_layout!=$layout) $rlt = "REBOOT";

fwrite("w",$ShellPath, "#!/bin/sh\n");
fwrite("a",$ShellPath, "echo \"[$0]-->Wan Change\" > /dev/console\n");

if($rlt=="REBOOT")
{
	fwrite("a",$ShellPath, "event DBSAVE > /dev/console\n");
	//For dir880, WAN type DHCP <-> PPPoE would reboot. However, it could not reboot immediately for wizard settings to avoid skip mydlink settings.
	if($Wizard=="1")
	{
		fwrite("a",$ShellPath, "echo 0 > /proc/net/ipct_hndctf\n");
		fwrite("a",$ShellPath, "service WAN restart > /dev/console\n");
	}
}
else if($rlt=="OK")
{
	fwrite("a",$ShellPath, "event DBSAVE > /dev/console\n");
	fwrite("a",$ShellPath, "service DEVICE.HOSTNAME restart > /dev/console\n");
	if ($MacAddress != "") { fwrite("a",$ShellPath, "service PHYINF.".$wan1_phyinf." restart > /dev/console\n"); }
	fwrite("a",$ShellPath, "service WAN restart > /dev/console\n");
	if($Type == "StaticBridged" || $Type == "DynamicBridged")
			{ fwrite("a",$ShellPath, "service BRIDGE restart > /dev/console\n");}
  fwrite("a",$ShellPath, "xmldbc -s /runtime/hnap/dev_status '' > /dev/console\n");	
	set("/runtime/hnap/dev_status", "ERROR");
}
else
{
	fwrite("a",$ShellPath, "echo \"We got a error in setting, so we do nothing...\" > /dev/console\n");
}
?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<SetWanSettingsResponse xmlns="http://purenetworks.com/HNAP1/">
<SetWanSettingsResult><?=$rlt?></SetWanSettingsResult>
</SetWanSettingsResponse>
</soap:Body>
</soap:Envelope>
