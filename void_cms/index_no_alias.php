<?php
include "site_vars.php";
error_reporting(0);
function getpage($page)
{
  global $void_config;
  $pagestr = file_get_contents($page);
  foreach ($void_config as $key => $val) {
	  $placeholder = '{{' . $key . '}}';
	  $pagestr = str_replace($placeholder, $val, $pagestr);
  }
  list($pageheader, $pagecontent) = preg_split('~(?:\r?\n){2}~', $pagestr, 2);  // split into 2 parts : before/after the first blank line
  preg_match("/^TITLE:(.*)$/m", $pageheader, $matches1);                        // for articles: title // for pages: title displayed in top-menu
  preg_match("/^AUTHOR:(.*)$/m", $pageheader, $matches2);                       // for articles only
  preg_match("/^DATE:(.*)$/m", $pageheader, $matches3);                         // for articles only
  preg_match("/^(NOMENU:1)$/m", $pageheader, $matches4);                        // for pages only: if NOMENU:1, no link in top-menu
  preg_match("/^URL:(.*)$/m", $pageheader, $matches5);                          // for articles: article's link; for pages: top-menu's link 
  preg_match("/^(NOHEAD:1)$/m", $pageheader, $matches6);                        // for pages only: if NOHEAD:1, no header after top menu
  return array($pagecontent, $matches1[1], trim($matches2[1]), $matches3[1], $matches4[1], trim($matches5[1]), $matches6[1]);
}
$siteroot = substr($_SERVER['PHP_SELF'], 0,  - strlen(basename($_SERVER['PHP_SELF']))); // must have trailing slash, we don't use dirname because it can produce antislash on Windows
$requestedpage = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === $siteroot) { $requestedpage = ""; }     // check if homepage 
$type =  strpos($_SERVER['REQUEST_URI'], 'article/') ? 'article' : 'page';
$pages = glob("./" . $type ."/*$requestedpage.{txt,md}", GLOB_BRACE);
if ($pages) { $page = $pages[0]; } else { $page = "./page/HIDDEN-404.txt"; $type = 'page'; }     // default 404 error page
list($pagecontent, $pagetitle, $pageauthor, $pagedate, $pagenomenu, $pageurl, $pagenohead) = getpage($page);
if (!$pageurl) { $a = pathinfo($page); $pageurl = $a['filename']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo (trim($pagetitle) ? "$sitename - $pagetitle" : "$sitename")?></title>
  <base href="<?php echo htmlspecialchars($siteroot, ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="header">
  <?php if(!$pagenohead && strlen($site_header) > 0) { ?><div class="logo"><a href="."><?php echo $site_header;?></a></div> <?php } ?>
  <ul class="menu">
    <?php
    $pages = glob("./page/*.{txt,md}", GLOB_BRACE);
    foreach($pages as $page)
    {
      list($menupagecontent, $menupagetitle, $menupageauthor, $menupagedate, $menupagenomenu, $menupageurl, $menupagenohead) = getpage($page);
      if (!$menupagenomenu) { echo "<li><a href=\"" . ($menupageurl ? $menupageurl : str_replace(' ', '', strtolower($menupagetitle))) . "\">$menupagetitle</a></li>"; }
    }
    ?>
  </ul>
</div>
<div class="main">

<?php
require 'Parsedown.php';

$b = new Parsedown();
if ($type === "article")
{ 
  echo "<div class=\"article\"><a href=\"article/$pageurl\"><h2 class=\"articletitle\">$pagetitle</h2><div class=\"articleinfo\">by $pageauthor, on $pagedate</div></a>";
  echo $b->text($pagecontent);
  echo "</div>";
} 
else if ($type === "page")
{
  echo '<div class="page">' . $b->text($pagecontent) . '</div>';
}

if ($requestedpage === $blogpagename)
{
  $pages = array_slice(array_reverse(glob("./article/*.{txt,md}", GLOB_BRACE)), $_GET['start'], 10);
  foreach($pages as $page)
  {
    list($pagecontent, $pagetitle, $pageauthor, $pagedate, $pagenomenu, $pageurl, $pagenohead) = getpage($page);
    if (!$pageurl) { $a = pathinfo($page); $pageurl = $a['filename']; }
    echo "<div class=\"article\"><a href=\"article/$pageurl\"><h2 class=\"articletitle\">$pagetitle</h2><div class=\"articleinfo\">by $pageauthor, on $pagedate</div></a>";
	$b = new Parsedown();
    echo $b->text($pagecontent);
    echo "</div>";
  }
  if ($_GET['start'] > 0) { echo "<a href=\"" . $blogpagename . (($_GET['start'] > 10) ? "?start=" . ($_GET['start'] - 10) : "") . "\">Newer articles</a>&nbsp; "; }
  if (count(array_slice(array_reverse(glob("./article/*.{txt,md}", GLOB_BRACE)), $_GET['start'], 11)) > 10) { echo "<a href=\"" . $blogpagename . "?start=" . ($_GET['start'] + 10) . "\">Older articles</a>"; }
}

?>
</div>
<div class="footer"><?php if($void_sys['show_footer_txt']) { ?><div class="center"><center><?php echo $void_sys['footer_txt']; ?></center></div><?php } ?></div>
<?php if($void_sys['show_footer']) { ?>
<div class="footer">
  <div class="left"><a href="">© <?php echo date('Y') . " " . $sitename; ?></a></div>
  <div class="right">Powered by <a href="<?php echo $void_sys['brand_url']; ?>" target="_blank"><?php echo $void_sys['brand_name']; ?></a>.</div>
</div>
<?php } ?>
</body>
</html>