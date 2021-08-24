<?
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";

include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/inet.php";
include "/htdocs/phplib/inf.php";
include "/htdocs/phplib/phyinf.php";
include "/htdocs/webinc/config.php";

$DEBUG_HNAP = "n";	//+++ Sammy, DEBUG mode default = n.

if($DEBUG_HNAP == "y")
{
	
	TRACE_info("== [ Set IPv6 Settings.php ]: DEBUG msgs ===");	
	
	TRACE_info("  ++ [ConnectionType] = ".$ConnectionType);
	
	if($ConnectionType == "IPv6_LinkLocalOnly")
	{
		TRACE_info("$LanUniqueLocalAddressStatus        = ".$LanUniqueLocalAddressStatus);
		TRACE_info("$LanUniqueLocalAddressDefaultPrefix = ".$LanUniqueLocalAddressDefaultPrefix);		
	}
	else if ($ConnectionType == "IPv6_Static")
	{
		TRACE_info("  $Address                = ".$Address);
		TRACE_info("  $SubnetPrefixLength     = ".$SubnetPrefixLength);		
		TRACE_info("  $DefaultGateway         = ".$DefaultGateway);		
		TRACE_info("  $PrimaryDNS             = ".$PrimaryDNS);		
		TRACE_info("  $LanAddress             = ".$LanAddress);			
	}
	else if ($ConnectionType == "IPv6_PPPoE")
	{
		TRACE_info("  $DhcpPd          = ".$DhcpPd);
		TRACE_info("  $PppoeNewSession = ".$PppoeNewSession);
		TRACE_info("  $Pppoetype       = ".$Pppoetype);				
	}	
	else if ($ConnectionType == "IPv6_6To4")
	{
		TRACE_info("  [IPv6_6To4] Still doing ...");
	}				
	
}

//+++ Jerry Kao, Add Bridge mode.
$Layout = query("/device/layout");

if ($Layout == "bridge")
{
	set(INF_getinfpath($WAN3)."/inet",   "INET-2"); 	 
	set(INF_getinfpath($WAN4)."/active", "1");	     
	set(INF_getinfpath($WAN4)."/inet",   "INET-3"); 	 
}
else
{
	//rbj, to avoid inet is null
	set(INF_getinfpath($WAN3)."/inet",   "INET-8"); 	//PreWAN()
	set(INF_getinfpath($WAN4)."/active", "1");	     
	set(INF_getinfpath($WAN4)."/inet",   "INET-9"); 	 

	set(INF_getinfpath($LAN4)."/inet",   "INET-7");	//PreLAN()
}


/*<--ParseAll()  */

$WAN      = $WAN4;
$WANllact = "";
$WANact   = "";

$LAN      = $LAN3;
$LANllact = "";
$LANact   = "";

$i = 1;
while($i < 3)
{		
	if(query(INF_getinfpath($WAN)."/active") == "1")
	{
		if(query(INET_getpathbyinf($WAN)."/ipv6/mode") == "LL") $WANllact=$WAN;
		else
		{
			if($WAN == $WAN3) $WANllact=$WAN;
			else $WANact=$WAN;
		}			
	}
	
	if(query(INF_getinfpath($LAN)."/active") == "1")
	{
		if(query(INET_getpathbyinf($LAN)."/ipv6/mode") == "LL") $LANllact = $LAN;
		else $LANact = $LAN;			
	}
	
	$WAN = $WAN3;
	$LAN = $LAN4;
	
	$i++;
}

$WAN3_infp	    = INF_getinfpath($WAN3);
$WAN4_infp	    = INF_getinfpath($WAN4);
$WANllact_infp  = INF_getinfpath($WANllact);
$WANact_infp    = INF_getinfpath($WANact);

$WAN3_inetp	    = INET_getpathbyinf($WAN3);
$WAN4_inetp	    = INET_getpathbyinf($WAN4);
$WANllact_inetp	= INET_getpathbyinf($WANllact);
$WANact_inetp   = INET_getpathbyinf($WANact);

$LAN3_infp	    = INF_getinfpath($LAN3);
$LAN4_infp	    = INF_getinfpath($LAN4);
$LANllact_infp	= INF_getinfpath($LANllact);
$LANact_infp    = INF_getinfpath($LANact);

$LAN3_inetp	    = INET_getpathbyinf($LAN3);
$LAN4_inetp	    = INET_getpathbyinf($LAN4);
$LANllact_inetp	= INET_getpathbyinf($LANllact);
$LANact_inetp   = INET_getpathbyinf($LANact);


$DHCPS6	      = XNODE_getpathbytarget("/dhcps6", "entry", "uid", "DHCPS6-1", 0);
$DHCPS6_infp  = XNODE_getpathbytarget("", "inf", "uid", "LAN-4", 0);
$DHCPS6_inet  = get("",$DHCPS6_infp."/inet");
$DHCPS6_inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $DHCPS6_inet, 0);

$lanulact_infp  = XNODE_getpathbytarget("", "inf", "uid", "LAN-6", 0);
$lanulact_inet  = get("",$lanulact_infp."/inet");
$lanulact_inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $lanulact_inet, 0);

$WAN1_infp	= INF_getinfpath($WAN1);
$WAN1_inetp = INET_getpathbyinf($WAN1);
$WAN5_infp	= INF_getinfpath($WAN5);
$WAN5_inetp	= INET_getpathbyinf($WAN5);

$rdnss = query("/device/rdnss");

if($DEBUG_HNAP == "y")
{
	TRACE_info("In ParseAll()");
	TRACE_info("  ++ $WANllact       = ".$WANllact);
	TRACE_info("  ++ $WANllact_inetp = ".$WANllact_inetp);		
	TRACE_info("  ++ $WANact         = ".$WANact);	
	TRACE_info("  ++ $WANact_inetp   = ".$WANact_inetp);
	
	TRACE_info("  ++ $WAN5     = ".$WAN5);		
		
	TRACE_info("  ++ $LANllact = ".$LANllact);
	TRACE_info("  ++ $LANact = ".$LANact);
}
/*  ParseAll()-->*/

/*<--PreWAN()  */
	set($WANllact_infp."/infnext", "");
	set($WANllact_infp."/infprevious", "");
	set($WANllact_infp."/child", "");
	set($WANllact_inetp."/ipv6/dhcpopt", "");
	set($WANllact_inetp."/ipv6/mode", "LL");   
	 
	set($WANact_infp."/infprevious", "");
	set($WANact_infp."/infnext", "");	
	set($WANact_infp."/infnext:2", "");
	set($WANact_infp."/child", "");
	set($WANact_infp."/childgz", "");
	
	set($WAN1_infp."/infprevious", "");
	set($WAN1_infp."/icmp", "DROP");	// Drop WAN ping except IPv6InIPv4Tunnel.
	
	
	set($WAN5_infp."/active", "0");
	set($WAN5_infp."/infnext", "");
	
	//ipv4 wan mode
	$dslite_mode = query($WAN1_inetp."/ipv4/ipv4in6/mode");
	
	if($DEBUG_HNAP == "y")
	{
		
		TRACE_info("  == [ Set IPv6 Settings.php ]: DEBUG msg 2 ... ");
		TRACE_info("  ++ [ConnectionType] == ".$ConnectionType);
		
		TRACE_info("  WAN1_infp     = ".$WAN1_infp);
		TRACE_info("  WAN1_inetp    = ".$WAN1_inetp);
		TRACE_info("  WANact_infp   = ".$WANact_infp);
		TRACE_info("  WANact_inetp  = ".$WANact_inetp);
		TRACE_info("  WANllact_infp  = ".$WANllact_infp);
		TRACE_info("  WANllact_inetp = ".$WANllact_inetp);
		TRACE_info("  WAN5_infp  = ".$WAN5_infp);
		TRACE_info("  WAN5_inetp = ".$WAN5_inetp);		
	}

	
	if($ConnectionType == "IPv6_Static")
	{
		set($WAN1_infp."/infnext", "");
		set($WAN1_infp."/child", "");
		set($WAN1_infp."/childgz", "");
		set($WANact_infp."/child", "");
		set($WANact_infp."/childgz", "");
		set($WANact_infp."/active", "1");

		set($WANllact_inetp."/addrtype", "ipv6");		
		
		if($dslite_mode=="dslite")	{	set($WANact_infp."/dhcpc6", "1");	} //for deslite
		else	{	set($WANact_infp."/dhcpc6", "0");	} //for deslite	
		
		set($WANact_inetp."/ipv6/mode", "STATIC");
		
		/* clear ppp10 addrtype */
		if(query($WAN1_inetp."/addrtype") == "ppp10")
		{			
			set($WAN1_inetp."/addrtype", "ppp4");
		}	
		
		/* ipaddr, prefix */
		set($WANact_inetp."/ipv6/ipaddr", $Address);
		set($WANact_inetp."/ipv6/prefix", $SubnetPrefixLength);
	
		/* gateway */
		if($DefaultGateway != "")	{	set($WANact_infp."/defaultroute", "1");	}
		else	{	set($WANact_infp."/defaultroute", "0");	}
		set($WANact_inetp."/ipv6/gateway", $DefaultGateway);
	
		/* dns */
		if($PrimaryDNS=="" && $SecondaryDNS=="")	{	set($WANact_inetp."/ipv6/dns/count", "0");	}
		else if($PrimaryDNS!="" && $SecondaryDNS!="")	{	set($WANact_inetp."/ipv6/dns/count", "2");	}
		else	{	set($WANact_inetp."/ipv6/dns/count", "1");	}
		//Web side should modified for DNS contains IPv4 addr. ex:  ::192.168.0.1 => ::c0a8:1 
		set($WANact_inetp."/ipv6/dns/entry:1", $PrimaryDNS);
		set($WANact_inetp."/ipv6/dns/entry:2", $SecondaryDNS);	
		
		/* dslite */
		if($dslite_mode=="dslite") 
		{
			set($WANact_infp."/infnext", "WAN-1");
			set($WANact_infp."/infnext:2", "LAN-4");
		}
		else	{ set($WANact_infp."/infnext", ""); }
		
		//+++ Jerry Kao, remove infnext = LAN-4 in Bridge mode.
		if ($Layout == "bridge")
		{
			set($WANact_infp."/infnext", "");
		}
		
	}
	else if($ConnectionType == "IPv6_AutoDetection")
	{
		set($WAN1_infp."/infnext", "");
		set($WAN1_infp."/child", "");			
		set($WAN1_infp."/childgz", "");
		set($WANact_infp."/active", "0");
		
		set($WANllact_inetp."/addrtype", "ipv6");
		
		set($WAN5_infp."/active", "1");
		set($WAN5_inetp."/ipv6/mode", "AUTODETECT");
		set($WAN5_inetp."/ipv6/detectuid/v4actuid", "WAN-1");
		set($WAN5_inetp."/ipv6/detectuid/v6lluid", "WAN-3");
		set($WAN5_inetp."/ipv6/detectuid/v6actuid", "WAN-4");

		if($DhcpPd == "Enable")
		{
			set($WAN5_infp."/child", "LAN-4");
			set($WAN5_infp."/childgz", "LAN-5");
			set($WAN5_inetp."/ipv6/dhcpopt", "IA-NA+IA-PD");//maybe change when service starts
		}	
		else
		{
			set($WAN5_infp."/child", "");
			set($WAN5_infp."/childgz", "");
			set($WAN5_inetp."/ipv6/dhcpopt", "");
			set($WAN5_infp."/infnext", "LAN-4");
		}
		
		/* dns */
		$dnscnt = 0;
		if($ObtainDNS == "Automatic")
		{
			set($WAN5_inetp."/ipv6/dns/entry:1", "");
			set($WAN5_inetp."/ipv6/dns/entry:2", "");
		}
		else
		{
			if($PrimaryDNS!="")
			{
				set($WAN5_inetp."/ipv6/dns/entry:1", $PrimaryDNS);
				$dnscnt++;
			}	
			if($SecondaryDNS!="")
			{
				set($WAN5_inetp."/ipv6/dns/entry:2", $SecondaryDNS);
				$dnscnt++;
			}			
		}
		set($WAN5_inetp."/ipv6/dns/count", $dnscnt);
	}							
	else if($ConnectionType == "IPv6_AutoConfiguration")
	{	
		set($WAN1_infp."/infnext", "");
		set($WAN1_infp."/child", "");
		set($WAN1_infp."/childgz", "");
		
		set($WANact_infp."/active", "1");
		set($WANact_infp."/defaultroute", "1");
		set($WANact_inetp."/ipv6/mode", "AUTO");

		set($WANllact_inetp."/addrtype", "ipv6");

		/* clear ppp10 addrtype */
		if(query($WAN1_inetp."/addrtype") == "ppp10")
		{			
			set($WAN1_inetp."/addrtype", "ppp4");
		}
		
		if($DhcpPd == "Enable")
		{
			set($WANact_infp."/child", "LAN-4");
			set($WANact_infp."/childgz", "LAN-5");
			set($WANact_inetp."/ipv6/dhcpopt", "IA-NA+IA-PD");//maybe change when service starts
		}	
		else
		{
			set($WANact_infp."/child", "");
			set($WANact_infp."/childgz", "");
			set($WANact_inetp."/ipv6/dhcpopt", "");
			if($dslite_mode == "dslite") { set($WANact_infp."/infnext:2", "LAN-4"); }
			else	{ set($WANact_infp."/infnext", "LAN-4"); }		
		}		

		//+++ Jerry Kao, remove infnext = LAN-4 in Bridge mode.
		if ($Layout == "bridge")
		{
			set($WANact_infp."/infnext", "");
		}		

		/* dns */
		$dnscnt = 0;
		if($ObtainDNS == "Automatic")
		{
			set($WANact_inetp."/ipv6/dns/entry:1", "");
			set($WANact_inetp."/ipv6/dns/entry:2", "");
		}
		else
		{
			if($PrimaryDNS!="")
			{
				set($WANact_inetp."/ipv6/dns/entry:1", $PrimaryDNS);
				$dnscnt++;
			}
			if($SecondaryDNS!="")	
			{
				set($WANact_inetp."/ipv6/dns/entry:2", $SecondaryDNS);
				$dnscnt++;
			}			
		}
		set($WANact_inetp."/ipv6/dns/count", $dnscnt);
		
		/* dslite */
		if($dslite_mode=="dslite")
		{
			set($WANact_infp."/infnext", "WAN-1");
			set($WANact_infp."/dhcpc6", "0");	
		}	
	}
	else if($ConnectionType=="IPv6_PPPoE")
	{			
		//TRACE_info("  == [IPv6_PPPoE] $WANact_infp = ".$WANact_infp);  // /inf:11  (WAN-4)
		//TRACE_info("  == [IPv6_PPPoE] $WANact_inetp= ".$WANact_inetp); // /inet/entry:9
		
		//+++ Jerry Kao, Init node /child to avoid /child is still "WAN-3", 
		//               when "Share with v4" to "Create new session".
		set($WAN1_infp."/child", "");
		set($WAN1_infp."/childgz", "");
				
		set($WAN1_infp."/infnext", "");		
		set($WANllact_infp."/inet", "INET-8");
		set($WANact_infp."/active", "1");
		set($WANact_infp."/defaultroute", "0");
		
		if($DhcpPd == "Enable")
		{
			if($PppoeNewSession == "SharedWithIPv4")
			{
				set($WAN1_infp."/child", "WAN-3");
				set($WANllact_infp."/inet", "");
				set($WANllact_infp."/infnext", "WAN-4");
				set($WANact_infp."/infprevious", "WAN-3");
			}
			else
			{
				set($WANllact_infp."/infnext", "WAN-4");
				set($WANllact_infp."/inet", "INET-8");
				set($WANact_infp."/infprevious", "WAN-3");				
			}
			
			set($WANact_infp."/child", "LAN-4");
			set($WANact_infp."/childgz", "LAN-5");
			set($WANact_inetp."/ipv6/dhcpopt", "IA-PD");
			//set($WANact_inetp."/ipv6/mode", "PPPDHCP");
			//set($WANact_inetp."/ipv6/mode", "AUTO");
		}
		else
		{			
			if($PppoeNewSession == "SharedWithIPv4")
			{
				set($WAN1_infp."/child", "WAN-3");  
				set($WANllact_infp."/inet", "");  
				set($WANllact_infp."/infnext", "WAN-4");  
				set($WANact_infp."/infprevious", "WAN-3"); 
			}
			else
			{	
				set($WANllact_infp."/infnext", "WAN-4");  
				set($WANllact_infp."/inet", "INET-8");  
				set($WANact_infp."/infprevious", "WAN-3");  
			}
			set($WANact_infp."/infnext", "LAN-4"); 			
			set($WANact_infp."/child", "");
			set($WANact_infp."/childgz", "");
			set($WANact_inetp."/ipv6/dhcpopt", "IA-NA");
			//set($WANact_inetp."/ipv6/mode", "PPPDHCP");
			//set($WANact_inetp."/ipv6/mode", "AUTO");	
		
		} // if($DhcpPd == "Enable")	
		
		if($Pppoetype =="Dynamic")
		{	
			set($WANact_inetp."/ipv6/mode", "AUTO");
		}
		else //Static
		{
			set($WANact_inetp."/ipv6/mode", "STATIC");
			set($WANact_inetp."/ipv6/ipaddr", $Address);
			set($WANact_inetp."/ipv6/gateway", "fe80::1");//fake
			set($WANact_inetp."/ipv6/prefix", "128");			
		}
		
		if($PppoeNewSession == "SharedWithIPv4")
		{
		   	set($WAN1_inetp."/addrtype", "ppp10");
			   	
		   	//+++ Jerry Kao, Username, Password, ServiceName, MTU, and Reconnection Mode 
		   	//               should follow PPPoE v4 in SharedWithIPv4. 			   	
		   	$PppoeUsername    = query($WAN1_inetp."/ppp4/username");
		   	$PppoePassword    = query($WAN1_inetp."/ppp4/password");
		   	$PppoeServiceName = query($WAN1_inetp."/ppp4/servicename");
			   	
		   	set($WAN1_inetp."/ppp6/username", $PppoeUsername);		 		
	  		set($WAN1_inetp."/ppp6/password", $PppoePassword);    		  	   		   		
		  	set($WAN1_inetp."/ppp6/pppoe/servicename", $PppoeServiceName);		  	
		   		
		  	set($WAN1_inetp."/ppp4/over", "eth");
		  	set($WAN1_inetp."/ppp6/over", "eth");
		   		
		  	set($WAN1_infp."/lowerlayer", "");
		
		   	if($Pppoetype=="Dynamic")
		   	{
		   		set($WAN1_inetp."/ppp6/static", "0");
		   		del($WAN1_inetp."/ppp6/ipaddr");			
		   	}
		   	else
		   	{
		   		set($WAN1_inetp."/ppp6/static", "1");
		   		set($WAN1_inetp."/ipv6/ipaddr", $Address);			
		   	}
		
		   	/* dns */
		   	$cnt=0;
		   	if($ObtainDNS == "Automatic")
		   	{
		   		set($WAN1_inetp."/ppp6/dns/entry:1","");
		   		set($WAN1_inetp."/ppp6/dns/entry:2","");
		   	}
		   	else
		   	{			
		   		if($PrimaryDNS!="")
		   		{
		   		    set($WAN1_inetp."/ppp6/dns/entry:1", $PrimaryDNS);
					$cnt++;
				}
				if($SecondaryDNS!="")
				{
					set($WAN1_inetp."/ppp6/dns/entry:2", $SecondaryDNS);
					$cnt++;
				}		
			}
			set($WAN1_inetp."/ppp6/dns/count", $cnt);				
		  		
			/* MTU */			
			$MTU_v4 = query($WAN1_inetp."/ppp4/mtu");				
			set($WAN1_inetp."/ppp6/mtu", $MTU_v4);			  					  	
		  		
			/* Reconnection Mode */	
			//+++ Jerry Kao, modified for follow the Reconnection Mode of IPv4 PPPoE.       														    						
			$dialup_v4 = query($WAN1_inetp."/ppp4/dialup/mode");
			set($WAN1_inetp."/ppp6/dialup/mode", $dialup_v4);		  		
		  		
			/* need to check DHCP-PD is enable or not */					  		
			set($WANllact_inetp."/addrtype", "ipv6");
			//set($WANact_inetp."/addrtype", "ipv6");
			//set($WANact_inetp."/ipv6/mode", "DHCP");
		}
		else	//create a new one
		{
			//+++ Jerry Kao, modified for avoid check username by fatlady when v4 is DHCP
			//               and v6 is PPPoE (Create new session).
			if (query($WAN1_inetp."/addrtype")=="ppp10")
			{
				set($WAN1_inetp."/addrtype", "ppp4");
			}		   		
		   		
			set($WANllact_inetp."/addrtype", "ppp6");
			set($WANllact_inetp."/ppp6/username", $PppoeUsername);
			set($WANllact_inetp."/ppp6/password", $PppoePassword);
		   		    
			set($WANllact_inetp."/ppp6/pppoe/servicename", $PppoeServiceName);
		   		
			set($WANllact_inetp."/ppp6/over", "eth");
		   		
			if($Pppoetype=="Dynamic")
			{
				set($WANllact_inetp."/ppp6/static", "0");
				del($WANllact_inetp."/ppp6/ipaddr");			
			}
			else
			{
				set($WANllact_inetp."/ppp6/static", "1");
				set($WANllact_inetp."/ppp6/ipaddr", $Address);			
			}		
				
			/* dns */
			$cnt=0;
			if($ObtainDNS == "Automatic")
			{
				set($WANllact_inetp."/ppp6/dns/entry:1","");
				set($WANllact_inetp."/ppp6/dns/entry:2","");
			}
			else
			{
				if($PrimaryDNS!="")
				{
					set($WANllact_inetp."/ppp6/dns/entry:1", $PrimaryDNS);
					$cnt++;
				}
				if($SecondaryDNS!="")
				{
					set($WANllact_inetp."/ppp6/dns/entry:2", $SecondaryDNS);
					$cnt++;
				}
			}
			set($WANllact_inetp."/ppp6/dns/count", $cnt);
			set($WANllact_inetp."/ppp6/mtu", $PppoeMTU);
				
			if($PppoeReconnectMode == "AlwaysOn")
			{
				set($WANllact_inetp."/ppp6/dialup/mode", "auto");
			}
			else
			{
				set($WANllact_inetp."/ppp6/dialup/mode", "manual");			
			}	
			//set($WANllact_inetp."/ppp6/dialup/mode", $PppoeReconnectMode);
				
			/* need to check DHCP-PD is enable or not */
			//set($WANact_inetp."/addrtype", "ipv6");
			//set($WANact_inetp."/ipv6/mode", "DHCP");							

		}   // if($PppoeNewSession == "SharedWithIPv4")

		//clear dns of wanact
		set($WANact_inetp."/ipv6/dns/count", "0");
		set($WANact_inetp."/ipv6/dns/entry:1", "");
		set($WANact_inetp."/ipv6/dns/entry:2", "");
	}
	else if($ConnectionType=="IPv6_IPv6InIPv4Tunnel")
	{	
		//+++ Jerry Kao, Accept WAN ping for IPv6InIPv4Tunnel.
		set($WAN1_infp."/icmp", "ACCEPT");
		
		set($WAN1_infp."/infnext", "WAN-4");
		set($WANact_infp."/infprevious", "WAN-1");
				
		if($DhcpPd == "Enable")
		{
			set($WANllact_infp."/infprevious", "WAN-4");  
			set($WANllact_infp."/child", "LAN-4");
			set($WANllact_infp."/childgz", "LAN-5");
			set($WANllact_inetp."/addrtype", "ipv6");
			set($WANllact_inetp."/ipv6/mode", "AUTO");
			set($WANllact_inetp."/ipv6/dhcpopt", "IA-PD");
			set($WANact_infp."/active", "1");
			set($WANact_infp."/infnext", "WAN-3");
			set($WANact_inetp."/ipv6/mode", "6IN4");
			if($DEBUG_HNAP == "y") TRACE_info("DhcpPd is enable in 6IN4 mode.");					
		}
		else
		{
			set($WANllact_infp."/infprevious", "");  
			set($WANact_infp."/child", "");
			set($WANact_infp."/childgz", "");
			set($WANllact_inetp."/addrtype", "ipv6");
			set($WANllact_inetp."/ipv6/mode", "LL");
			set($WANllact_inetp."/ipv6/dhcpopt", "");
			set($WANact_infp."/active", "1");
			set($WANact_infp."/infnext", "");
			set($WANact_inetp."/ipv6/mode", "6IN4");
			if($DEBUG_HNAP == "y") TRACE_info("DhcpPd is disable in 6IN4 mode.");			
		}
		
		/* clear ppp10 addrtype */
		if(query($WAN1_inetp."/addrtype") == "ppp10")
		{			
			set($WAN1_inetp."/addrtype", "ppp4");
		}	
		
		/* ipaddr, prefix */
		set($WANact_inetp."/ipv6/ipaddr", $6In4LocalIPv6Address);
		set($WANact_inetp."/ipv6/prefix", $SubnetPrefixLength);
		
		/* gateway */
		set($WANact_infp."/defaultroute", "1");	
		set($WANact_inetp."/ipv6/gateway", $6In4RemoteIPv6Address);			

		/* dns */
		$dnscnt=0;
		if($ObtainDNS == "Automatic")
		{
			set($WANact_inetp."/ipv6/dns/entry:1", "");
			set($WANact_inetp."/ipv6/dns/entry:2", "");
		}
		else
		{
			if($PrimaryDNS!="")
			{
				set($WANact_inetp."/ipv6/dns/entry:1", $PrimaryDNS);
				$dnscnt++;
			}
			if($SecondaryDNS!="")
			{
				set($WANact_inetp."/ipv6/dns/entry:2", $SecondaryDNS);
				$dnscnt++;	
			}
		}
		set($WANact_inetp."/ipv6/dns/count", $dnscnt);
		
		/* set ipv4 address for server */
		set($WANact_inetp."/ipv6/ipv6in4/remote", $6In4RemoteIPv4Address);
	}
	else if($ConnectionType=="IPv6_6To4")
	{
		set($WAN1_infp."/infnext", "WAN-4");
		set($WAN1_infp."/child", "");

		set($WANact_infp."/child", "LAN-4");
		set($WANact_infp."/infprevious", "WAN-1");
		set($WANact_infp."/active", "1");
		set($WANact_infp."/defaultroute", "1");	
		set($WANact_inetp."/ipv6/mode", "6TO4");
		set($WANact_inetp."/ipv6/ipv6in4/relay", $6To4Relay);

		set($WANllact_inetp."/addrtype", "ipv6");

		/* clear ppp10 addrtype */
		if(query($WAN1_inetp."/addrtype") == "ppp10")  
		{			
			set($WAN1_inetp."/addrtype", "ppp4");
		}

		/* dns */
		$dnscnt=0;
		if($PrimaryDNS!="")		$dnscnt++;
		if($SecondaryDNS!="")	$dnscnt++;
		set($WANact_inetp."/ipv6/dns/count", $dnscnt);
		set($WANact_inetp."/ipv6/dns/entry:1", $PrimaryDNS);
		set($WANact_inetp."/ipv6/dns/entry:2", $SecondaryDNS);
		
		/*	
		if(cut_count($LanAddress, ":")!=1)
		{
			$slaid = cut($LanAddress, cut_count($LanAddress, ":")-3, ":");
			$slaid = strtoul($slaid, 16);
		}
		else $slaid = strtoul($LanAddress, 16);
		*/
		$slaid = strtoul($slaid, 16);
		set($WANact_inetp."/ipv6/ipv6in4/ipv6to4/slaid", $slaid);
	}			
	else if($ConnectionType=="IPv6_6RD")
	{		
		set($WAN1_infp."/infnext", "WAN-4");
		
		set($WANact_infp."/child", "LAN-4");
		set($WANact_infp."/infprevious", "WAN-1");
		set($WANact_infp."/active", "1");
		set($WANact_infp."/defaultroute", "1");	
		set($WANact_inetp."/ipv6/mode", "6RD");

		set($WANllact_inetp."/addrtype", "ipv6");

		if($hub_spoke == "Enable")
		{
			set($WANact_inetp."/ipv6/ipv6in4/rd/hubspokemode", "1");
		}
		else
		{
			set($WANact_inetp."/ipv6/ipv6in4/rd/hubspokemode", "0");
		}
		
		if ($6Rd_Configuration=="Manual")
		{
			set($WANact_inetp."/ipv6/ipv6in4/rd/ipaddr", $6Rd_IPv6Prefix);
			set($WANact_inetp."/ipv6/ipv6in4/rd/prefix", $6Rd_IPv6PrefixLength);
			set($WANact_inetp."/ipv6/ipv6in4/rd/v4mask", $6Rd_IPv4MaskLength);
			set($WANact_inetp."/ipv6/ipv6in4/relay", $6Rd_BorderRelayIPv4Address);
		}
		else
		{
			set($WANact_inetp."/ipv6/ipv6in4/rd/ipaddr", "");
			set($WANact_inetp."/ipv6/ipv6in4/rd/prefix", "");
			set($WANact_inetp."/ipv6/ipv6in4/rd/v4mask", "");
			set($WANact_inetp."/ipv6/ipv6in4/relay", "");
		}

		/* clear ppp10 addrtype */
		if(query($WAN1_inetp."/addrtype") == "ppp10")
		{			
			set($WAN1_inetp."/addrtype", "ppp4");
		}

		/* dns */
		$dnscnt=0;
		if($PrimaryDNS!="")		$dnscnt++;
		if($SecondaryDNS!="")	$dnscnt++;
		set($WANact_inetp."/ipv6/dns/count", $dnscnt);
		set($WANact_inetp."/ipv6/dns/entry:1", $PrimaryDNS);
		set($WANact_inetp."/ipv6/dns/entry:2", $SecondaryDNS);
	}
	else if($ConnectionType == "IPv6_LinkLocalOnly")
	{
		/* clear ppp10 addrtype */
		if(query($WAN1_inetp."/addrtype") == "ppp10")
		{			
			set($WAN1_inetp."/addrtype", "ppp4");
		}
		set($WAN1_infp."/infnext", "");
		set($WAN1_infp."/child", "");
		set($WAN1_infp."/childgz", "");
		
		set($WANact_infp."/active", "0");
		set($WANact_infp."/child", "");
		set($WANact_infp."/childgz", "");
		set($WANact_inetp."/ipv6/mode", "");
		set($WANact_infp."/active", "0");
		
		set($WANllact_inetp."/addrtype", "ipv6");
		set($WANllact_inetp."/mode", "LL");
	}	

/*  PreWAN()-->*/

/*<--PreLAN()  */

	//rbj, disable dns relay for dir815
	set($LANact_infp."/dnsrelay", "0");
	set($LANact_infp."/infprevious", "");
	set($LANact_infp."/infnext", "");

	//get "wan mode" then set lan info
	if($ConnectionType == "IPv6_Static")
	{
		set($LANact_infp."/active", "1");
		set($LANact_infp."/infprevious", "");
		set($LANact_inetp."/ipv6/mode", "STATIC");
		
		/* ipaddr, prefix*/
		set($LANact_inetp."/ipv6/ipaddr", $LanAddress);
		set($LANact_inetp."/ipv6/prefix", $LanAddressPrefixLength);
	}
	else if($ConnectionType=="IPv6_AutoDetection" || $ConnectionType=="IPv6_AutoConfiguration")
	{
		if($DhcpPd != "Enable")
		{
			set($LANact_infp."/active", "1");
			set($LANact_infp."/inet", "INET-7");
			set($LANact_infp."/infprevious", "WAN-4");
			$LANact_inetp = XNODE_getpathbytarget("/inet", "entry", "uid", query($LANact_infp."/inet"), 0);
			set($LANact_inetp."/ipv6/mode", "STATIC");		

			/* ipaddr, prefix*/
			set($LANact_inetp."/ipv6/ipaddr", $LanAddress);
			set($LANact_inetp."/ipv6/prefix", $LanAddressPrefixLength);
		}
		else
		{
			set($LANact_infp."/active", "1");
			set($LANact_infp."/inet", "");
		}
	} /* DHCP and RA is for very old model Joseph Chao 20121128*/
	else if($ConnectionType=="IPv6_PPPoE")
	{
		if($DhcpPd != "Enable")
		{
			set($LANact_infp."/active", "1");
			set($LANact_infp."/inet", "INET-7");
			set($LANact_infp."/infprevious", "WAN-4");
			set($LANact_inetp."/ipv6/mode", "STATIC");

			/* ipaddr, prefix*/
			set($LANact_inetp."/ipv6/ipaddr", $LanAddress);
			set($LANact_inetp."/ipv6/prefix", $LanAddressPrefixLength);
		}
		else
		{
			set($LANact_infp."/active", "1");
			set($LANact_infp."/inet", "");
		}
	}
	else if($ConnectionType=="IPv6_IPv6InIPv4Tunnel")
	{
		if($DhcpPd == "Enable")
		{
			set($LANact_infp."/active", "1");
			set($LANact_infp."/inet", "");
		}
		else
		{
			set($LANact_infp."/active", "1");
			set($LANact_inetp."/ipv6/mode", "STATIC");

			/* ipaddr, prefix*/
			set($LANact_inetp."/ipv6/ipaddr", $LanAddress);
			set($LANact_inetp."/ipv6/prefix", $LanAddressPrefixLength);
		}
	}
	else if($ConnectionType=="IPv6_6To4")
	{
		set($LANact_infp."/active", "1");
		set($LANact_infp."/inet", "");
	}
	else if($ConnectionType=="IPv6_6RD")
	{
		set($LANact_infp."/active", "1");
		set($LANact_infp."/inet", "");
	}
	else if($ConnectionType=="IPv6_LinkLocalOnly")
	{
		TRACE_debug("===Set_IPv6_LinkLocalOnly===");
		set($LANact_infp."/inet", "");

		//for ULA
		set($lanulact_inetp."/ipv6/mode", "UL");
		
		if($LanUniqueLocalAddressStatus == "Enable")
		{
			//TRACE_debug("===EnableULA===");
			set($lanulact_infp."/active", "1");
		}
		else
		{
			//TRACE_debug("===DisableULA===");
			set($lanulact_infp."/active", "0");
		}
		
		if($LanUniqueLocalAddressDefaultPrefix == "Enable")
		{
			//TRACE_debug("===EnableULADefault===");
			set($lanulact_inetp."/ipv6/ipaddr", "");
			set($lanulact_inetp."/ipv6/prefix", "");
			set($lanulact_inetp."/ipv6/staticula", "0");
		}
		else
		{
			//TRACE_debug("===DisableULADefault===");
			set($lanulact_inetp."/ipv6/ipaddr", $LanUniqueLocalAddressPrefix);
			set($lanulact_inetp."/ipv6/prefix", "64");
			set($lanulact_inetp."/ipv6/staticula", "1");
			//TRACE_debug("===LanUniqueLocalAddressPrefix = ".$LanUniqueLocalAddressPrefix);
		}
	}
	
	//Disable ULA
	if($ConnectionType!="IPv6_LinkLocalOnly")
	{
		//TRACE_debug("===Not LL _ DisableULA===");
		set($lanulact_infp."/active", "0");		
	}
	
	if($LanIPv6AddressAutoAssignment=="Enable")
	{
		set($LANact_infp."/dhcps6", "DHCPS6-1");
		set($DHCPS6_infp."/dhcps6", "DHCPS6-1");
		
		// HuanYao: Need to clean the variables before set them.
		set($LANact_inetp."/ipv6/preferlft", "");
		set($LANact_inetp."/ipv6/validlft", "");
		set($LANact_inetp."/ipv6/routerlft", "");	
		
		if($LanAutoConfigurationType=="SLAAC_RDNSS")
		{
			set("/device/rdnss", "1");
			set($DHCPS6."/mode", "STATELESS");
			if($LanRouterAdvertisementLifeTime!="") 
				{	set($LANact_inetp."/ipv6/routerlft", 60*$LanRouterAdvertisementLifeTime);	}
		}
		else if($LanAutoConfigurationType=="SLAAC_StatelessDhcp")
		{
			set("/device/rdnss", "0");
			set($DHCPS6."/mode", "STATELESS");
			if($LanRouterAdvertisementLifeTime!="") 
				{	set($LANact_inetp."/ipv6/routerlft", 60*$LanRouterAdvertisementLifeTime);	}
		}
		else if($LanAutoConfigurationType=="Stateful")
		{
			set($DHCPS6."/mode", "STATEFUL");
			
			if(cut_count($LanIPv6AddressRangeStart, ":")!=1)
			{
				$str = "::".scut($LanIPv6AddressRangeStart, 0, "::00");
				set($DHCPS6."/start", $str);
				$str = strtoul(scut($LanIPv6AddressRangeEnd, 0, "::00"), 16) - strtoul(scut($LanIPv6AddressRangeStart, 0, "::00"), 16) + 1;
				set($DHCPS6."/count", $str);				
			}
			else
			{
				$str = "::".$LanIPv6AddressRangeStart;
				set($DHCPS6."/start", $str);
				$str = strtoul($LanIPv6AddressRangeEnd, 16) - strtoul($LanIPv6AddressRangeStart, 16) + 1;
				set($DHCPS6."/count", $str);
			}
			
			if($LanDhcpLifeTime!="")
			{
				set($LANact_inetp."/ipv6/preferlft", 60*$LanDhcpLifeTime);
				set($LANact_inetp."/ipv6/validlft", 2*60*$LanDhcpLifeTime);
			}
		}
	}
	else
	{
		set($LANact_infp."/dhcps6", "");
		set($DHCPS6_infp."/dhcps6", "");
	}
	
	if($DEBUG_HNAP == "y")
	{
		TRACE_info("== [ Set IPv6 Settings.php ]: LAN msgs ===");	
		
		TRACE_info("$DhcpPd = ".$DhcpPd);
		TRACE_info("$LanIPv6AddressAutoAssignment = ".$LanIPv6AddressAutoAssignment);
		TRACE_info("$LanAutomaticDhcpPd = ".$LanAutomaticDhcpPd);
		TRACE_info("$LanAutoConfigurationType = ".$LanAutoConfigurationType);
		TRACE_info("$LANact_inetp = ".$LANact_inetp);
		TRACE_info("  $Lan_RA_LifeTime = ".$LanRouterAdvertisementLifeTime);	
		TRACE_info("$LanIPv6AddressRangeStart = ".$LanIPv6AddressRangeStart);	
		TRACE_info("$LanIPv6AddressRangeEnd = ".$LanIPv6AddressRangeEnd);	
		
	}
	
	/* set lan enable pd */	
	if($ConnectionType=="IPv6_Static" || $ConnectionType=="IPv6_6RD" || $ConnectionType=="IPv6_6To4" || $ConnectionType=="IPv6_LinkLocalOnly")
	{
		set($DHCPS6."/pd/enable", "0");
	}
	else if($ConnectionType=="IPv6_IPv6InIPv4Tunnel" || $ConnectionType=="IPv6_AutoConfiguration" || $ConnectionType=="IPv6_PPPoE" || $ConnectionType=="IPv6_AutoDetection")
	{
		if($LanAutomaticDhcpPd=="Enable")
		{
			set($DHCPS6."/pd/enable", "1");
			set($DHCPS6."/pd/mode", "1"); /* 0: generic, 1: dlink */	
		}
		else
		{
			set($DHCPS6."/pd/enable", "0");
		}	
	}
/*  PreLAN()-->*/

set("/device/v6modechange", "1");

fwrite("w",$ShellPath, "#!/bin/sh\n");
fwrite("a",$ShellPath, "echo [$0] > /dev/console\n");

if($result == "OK")
{
	fwrite("a",$ShellPath, "event DBSAVE > /dev/console\n");		
		
	fwrite("a",$ShellPath, "phpsh /etc/events/INF-RESTART.php PREFIX=WAN > /dev/console\n");//Event "WAN.RESTART"
	fwrite("a",$ShellPath, "phpsh /etc/events/INF-RESTART.php PREFIX=LAN > /dev/console\n");//Event "LAN.RESTART"
	/*
	if($ConnectionType=="IPv6_LinkLocalOnly")
	{
		fwrite("a",$ShellPath, "phpsh /etc/events/INF-RESTART.php PREFIX=LAN > /dev/console\n");//Event "LAN.RESTART"
	}
	*/
	fwrite("a",$ShellPath, "xmldbc -s /runtime/hnap/dev_status '' > /dev/console\n");
	set("/runtime/hnap/dev_status", "ERROR");
}
else
{
	fwrite("a",$ShellPath, "echo \"We got a error in setting, so we do nothing...\" > /dev/console\n");	
}

?>
<soap:Envelope 
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
	<soap:Body>
		<?	
		if($ConnectionType=="IPv6_LinkLocalOnly")
		{
			echo '	<SetIPv6LinkLocalOnlySettingsResponse xmlns="http://purenetworks.com/HNAP1/">\n';
			echo "		<SetIPv6LinkLocalOnlySettingsResult>".$result."</SetIPv6LinkLocalOnlySettingsResult>\n";
			echo '	</SetIPv6LinkLocalOnlySettingsResponse>\n';
		}
		
		if($ConnectionType=="IPv6_AutoConfiguration")
		{
			echo '	<SetIPv6AutoConfigurationSettingsResponse xmlns="http://purenetworks.com/HNAP1/">\n';
			echo "		<SetIPv6AutoConfigurationSettingsResult>".$result."</SetIPv6AutoConfigurationSettingsResult>\n";
			echo '	</SetIPv6AutoConfigurationSettingsResponse>\n';
		}	
		
		if($ConnectionType=="IPv6_Static")
		{
			echo '	<SetIPv6StaticSettingsResponse xmlns="http://purenetworks.com/HNAP1/">\n';
			echo "		<SetIPv6StaticSettingsResult>".$result."</SetIPv6StaticSettingsResult>\n";
			echo '	</SetIPv6StaticSettingsResponse>\n';
		}	
		
		if($ConnectionType=="IPv6_Pppoe")
		{
			echo '	<SetIPv6PppoeSettingsResponse xmlns="http://purenetworks.com/HNAP1/">\n';
			echo "		<SetIPv6PppoeSettingsResult>".$result."</SetIPv6PppoeSettingsResult>\n";
			echo '	</SetIPv6PppoeSettingsResponse>\n';
		}	
		
		if($ConnectionType=="IPv6_6To4")
		{
			echo '	<SetIPv66to4TunnelSettingsResponse xmlns="http://purenetworks.com/HNAP1/">\n';
			echo "		<SetIPv66to4TunnelSettingsResult>".$result."</SetIPv66to4TunnelSettingsResult>\n";
			echo '	</SetIPv66to4TunnelSettingsResponse>\n';
		}	
		
		if($ConnectionType=="IPv6_6RD")
		{
			echo '	<SetIPv66rdTunnelSettingsResponse xmlns="http://purenetworks.com/HNAP1/">\n';
			echo "		<SetIPv66rdTunnelSettingsResult>".$result."</SetIPv66rdTunnelSettingsResult>\n";
			echo '	</SetIPv66rdTunnelSettingsResponse>\n';
		}	
		
		if($ConnectionType=="IPv6_IPv6InIPv4Tunnel")
		{
			echo '	<SetIPv66in4TunnelSettingsResponse xmlns="http://purenetworks.com/HNAP1/">\n';
			echo "		<SetIPv66in4TunnelSettingsResult>".$result."</SetIPv66in4TunnelSettingsResult>\n";
			echo '	</SetIPv66in4TunnelSettingsResponse>\n';
		}	
		
		if($ConnectionType=="IPv6_AutoDetection")
		{
			echo '	<SetIPv6AutoDetectionSettingsResponse xmlns="http://purenetworks.com/HNAP1/">\n';
			echo "		<SetIPv6AutoDetectionSettingsResult>".$result."</SetIPv6AutoDetectionSettingsResult>\n";
			echo '	</SetIPv6AutoDetectionSettingsResponse>\n';
		}
	?></soap:Body>
</soap:Envelope>
