<?
/* vi: set sw=4 ts=4: */
foreach ("/device/account/entry")
{
	if (query("name")!="" && query("group")=="0")
	{
		//echo query("name").":".query("password")."\n";
		echo query("name").":x\n";//Modify for hide plain passwd, johnson
	}
}
?>
