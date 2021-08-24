<? include "/htdocs/phplib/html.php";
if($Remove_XML_Head_Tail != 1)	{HTML_hnap_200_header();}

include "/htdocs/phplib/xnode.php";
include "/htdocs/webinc/config.php";
include "/htdocs/phplib/encrypt.php";
$path_inf_wan1 = XNODE_getpathbytarget("", "inf", "uid", $WAN1, 0);
$path_run_inf_wan1 = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN1, 0);	
			
if(get("x", $path_inf_wan1."/ddns4")!="")	$DDNS4_Enabled = true;
else										$DDNS4_Enabled = false;

$DDNS4_ServerAddress	= get("x", "/ddns4/entry:1/provider");
/*
	Hans says DDNS server is only remained dyndns.com, others are manual input by users.
	However our router ddnsd only mapping some of specific DDNS server address. It should be modified later.
	provider	=>	Server Address (Web Show)
	DYNDNS			dyndns.com
	DLINK			dlinkddns.com
	DLINK.COM.CN	dlinkddns.com.cn
	CYBERGATE		cybergate.planex.co.jp
	IOBB			iobb.net
*/
if($DDNS4_ServerAddress=="DYNDNS")			{$DDNS4_ServerAddress="dyndns.com";}
else if($DDNS4_ServerAddress=="DLINK")			{$DDNS4_ServerAddress="dlinkddns.com";}
else if($DDNS4_ServerAddress=="DLINK.COM.CN")	{$DDNS4_ServerAddress="dlinkddns.com.cn";}
else if($DDNS4_ServerAddress=="CYBERGATE")		{$DDNS4_ServerAddress="cybergate.planex.co.jp";}
else if($DDNS4_ServerAddress=="IOBB")			{$DDNS4_ServerAddress="iobb.net";}
$DDNS4_Hostname 		= get("x", "/ddns4/entry:1/hostname");
$DDNS4_Username		= get("x", "/ddns4/entry:1/username");
$DDNS4_Password		= get("x", "/ddns4/entry:1/password");
$DDNS4_Timeout		= get("x", "/ddns4/entry:1/interval")/60;

if(get("x", $path_run_inf_wan1."/ddns4/valid")=="1")
{
	$status = get("x", $path_run_inf_wan1."/ddns4/status");
	$result = get("x", $path_run_inf_wan1."/ddns4/result");
	if ($status == "IDLE")
	{
		if($result == "SUCCESS")$DDNS4_Status = "Connected";
		else					$DDNS4_Status = "Disconnected";
	}
	else
	{
		$DDNS4_Status = "Disconnected";
	}
}
else
{	
	$DDNS4_Status = "Disconnected";
}

?>
<? if($Remove_XML_Head_Tail != 1)	{HTML_hnap_xml_header();}?>
		<GetDynamicDNSSettingsResponse xmlns="http://purenetworks.com/HNAP1/">
			<GetDynamicDNSSettingsResult>OK</GetDynamicDNSSettingsResult>
			<Enabled><?=$DDNS4_Enabled?></Enabled> 
			<ServerAddress><?=$DDNS4_ServerAddress?></ServerAddress> 
			<Hostname><?=$DDNS4_Hostname?></Hostname> 
			<Username><?=$DDNS4_Username?></Username> 
			<Password><? echo AES_Encrypt128($DDNS4_Password); ?></Password>
			<Timeout><?=$DDNS4_Timeout?></Timeout> 
			<Status><?=$DDNS4_Status?></Status>
		</GetDynamicDNSSettingsResponse>
<? if($Remove_XML_Head_Tail != 1)	{HTML_hnap_xml_tail();}?>
