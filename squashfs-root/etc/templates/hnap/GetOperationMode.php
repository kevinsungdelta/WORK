<? include "/htdocs/phplib/html.php";
if($Remove_XML_Head_Tail != 1)	{HTML_hnap_200_header();}

include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/webinc/config.php";

function echoCurrentOPMode($WLAN)
{
	if(get("", "/device/layout")=="router")
	{
		$CurrentOPMode = "WirelessRouter";
	}
	else if(get("", "/device/layout")=="bridge")
	{
		if(query("/device/op_mode")=="repeater_ext")
			{$CurrentOPMode="WirelessRepeaterExtender";}
		else
			{$CurrentOPMode = "WirelessBridge";}
	}
	echo "\t\t\t\t<CurrentOPMode>".$CurrentOPMode."</CurrentOPMode>\n";
}

function echoAvailableOPMode($WLAN)
{
	include "/htdocs/webinc/config.php";
	echo "\t\t\t\t<AvailableOPMode>\n";
	if($FEATURE_NOAPMODE != 1)
	{echo "\t\t\t\t\t<string>WirelessBridge</string>\n";}
	if(get("", "/runtime/device/router/mode") != "")
	{echo "\t\t\t\t\t<string>WirelessRouter</string>\n";}
	echo "\t\t\t\t</AvailableOPMode>\n";
}
?>
<? if($Remove_XML_Head_Tail != 1)	{HTML_hnap_xml_header();}?>
		<GetOperationModeResponse xmlns="http://purenetworks.com/HNAP1/">
			<GetOperationModeResult>OK</GetOperationModeResult>
<?
			if(XNODE_getpathbytarget("", "phyinf", "uid", $WLAN1, 0) != "")
			{
				echo "\t\t\t<OperationModeList>\n";
				echo "\t\t\t\t<RadioID>RADIO_2.4GHz</RadioID>\n";
				echoCurrentOPMode($WLAN1);
				echoAvailableOPMode($WLAN1);
				echo "\t\t\t</OperationModeList>\n";
			}
			if(XNODE_getpathbytarget("", "phyinf", "uid", $WLAN1_GZ, 0) != "")
			{
				echo "\t\t\t<OperationModeList>\n";
				echo "\t\t\t\t<RadioID>RADIO_2.4G_Guest</RadioID>\n";
				echoCurrentOPMode($WLAN1_GZ);
				echoAvailableOPMode($WLAN1_GZ);
				echo "\t\t\t</OperationModeList>\n";
			}
			if(XNODE_getpathbytarget("", "phyinf", "uid", $WLAN2, 0) != "")
			{
				echo "\t\t\t<OperationModeList>\n";
				echo "\t\t\t\t<RadioID>RADIO_5GHz</RadioID>\n";
				echoCurrentOPMode($WLAN2);
				echoAvailableOPMode($WLAN2);
				echo "\t\t\t</OperationModeList>\n";
			}
			if(XNODE_getpathbytarget("", "phyinf", "uid", $WLAN2_GZ, 0) != "")
			{
				echo "\t\t\t<OperationModeList>\n";
				echo "\t\t\t\t<RadioID>RADIO_5G_Guest</RadioID>\n";
				echoCurrentOPMode($WLAN2_GZ);
				echoAvailableOPMode($WLAN2_GZ);
				echo "\t\t\t</OperationModeList>\n";
			}
?>		</GetOperationModeResponse>
<? if($Remove_XML_Head_Tail != 1)	{HTML_hnap_xml_tail();}?>
