<? include "/htdocs/phplib/html.php";
HTML_hnap_200_header();
?>
<? HTML_hnap_xml_header();?>
    <GetMultipleHNAPsResponse xmlns="http://purenetworks.com/HNAP1/">
    	<GetMultipleHNAPsResult>OK</GetMultipleHNAPsResult><?
		$Remove_XML_Head_Tail=1;
		$HNAPs="GetClientInfo GetScheduleSettings GetWanSettings GetIPv6Status GetRouterLanSettings GetWLanRadioSettings GetWLanRadioSecurity GetDeviceSettings GetNetworkSettings GetAdvNetworkSettings GetUSBStorageSettings GetUSBStorageDevice GetDLNA GetSMBStatus GetSMBSettings GetDMZSettings GetFirewallSettings GetIPv4FirewallSettings GetIPv6FirewallSettings GetIPv6SimpleSecurity GetIPv6IngressFiltering GetIPv6Settings GetIPv6PppoeSettings GetIPv66in4TunnelSettings GetIPv66rdTunnelSettings GetIPv66to4TunnelSettings GetIPv6AutoConfigurationSettings GetIPv6AutoDetectionSettings GetIPv6LinkLocalOnlySettings GetIPv6StaticSettings GetPortForwardingSettings GetVirtualServerSettings GetDynamicDNSSettings GetSysLogSettings GetSysEmailSettings GetAdministrationSettings GetInterfaceStatistics GetFirmwareSettings GetQoSSettings GetDynamicDNSIPv6Settings GetWanStatus GetGuestZoneRouterSettings GetWLanRadios GetMACFilters2 GetWiFiVerifyAlpha GetAPClientSettings GetClientInfoDemo GetClientInfoStatusDemo GetListDirectory GetFacebookWiFiSettings GetGuestZoneAccessAlpha GetSmartconnectSettings";
		$i=0;
		while(scut($HNAPs, $i, "")!="")
		{
	    	$HNAP_Name = scut($HNAPs, $i, "");
	    	$i++;
	    	if(exist("/runtime/hnap/GetMultipleHNAPs/".$HNAP_Name)==1)
	    	{
	    		if(isfile("/etc/templates/hnap/".$HNAP_Name.".php")==1)
	    		{
	    			del("/runtime/hnap/".$HNAP_Name);
	    			mov("/runtime/hnap/GetMultipleHNAPs/".$HNAP_Name, "/runtime/hnap");
	    			dophp("load", "/etc/templates/hnap/".$HNAP_Name.".php");
	    			$i=0;//Care the same HNAP actions with different parameter.
	    		}
	    	}
    	}

		/*
  		foreach("/runtime/hnap/GetMultipleHNAPs/HNAP")
  		{
			$paran = get("", "Parameter#");
			$i = 1;
			while($i <= $paran)
			{
				set("/runtime/hnap/".get("", "Name")."/".get("", "Parameter:".$i), get("", "Value:".$i));
				$i++;
			}

			if(isfile("/etc/templates/hnap/".get("", "Name").".php")==1)
			{dophp("load", "/etc/templates/hnap/".get("", "Name").".php");}
  		}
  		*/
  ?></GetMultipleHNAPsResponse>
<? HTML_hnap_xml_tail();?>
