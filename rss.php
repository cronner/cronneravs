<?php
/**
 * RSS 2.0 feed with the latest published videos.
 *
 * This is a cleaned-up version of the feed that shipped with the community
 * fork. Compared to the original it:
 *   - drops the dead code that referenced $video before the loop,
 *   - XML-escapes every value (titles, descriptions, config strings),
 *   - emits a proper RFC 2822 <pubDate> based on the video timestamp,
 *   - uses the video permalink as a stable, unique <guid>,
 *   - builds the thumbnail URL with this CMS' own get_thumb_url() helper
 *     instead of a hard-coded "thumb.jpg" path,
 *   - prevents CDATA injection in the description.
 */

//File access
define('_ENTER', true);
define('_VALID', true);

require 'include/config.php';
require 'include/function_global.php';
require 'include/function_thumbs.php';

/** Maximum number of items in the feed. */
define('RSS_ITEM_LIMIT', 50);

/**
 * Escape a value so it is safe inside an XML text node or attribute.
 *
 * @param  mixed  $value
 * @return string
 */
function rss_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/**
 * Make a value safe to embed inside a CDATA section by splitting any
 * "]]>" sequence, which would otherwise terminate the section early.
 *
 * @param  mixed  $value
 * @return string
 */
function rss_cdata($value)
{
    return str_replace(']]>', ']]]]><![CDATA[>', (string) $value);
}

$sql    = "SELECT VID, title, description, addtime, adddate, thumb
           FROM video
           WHERE active = '1'
           ORDER BY addtime DESC
           LIMIT " . RSS_ITEM_LIMIT;
$rs     = $conn->execute($sql);
$videos = $rs->getrows();

$site_title = rss_escape($config['site_title']);
$site_desc  = rss_escape($config['meta_description']);
$site_url   = rss_escape($config['BASE_URL']);
$logo_url   = rss_escape($config['BASE_URL'] . '/images/logo/logo.png');

header('Content-Type: text/xml; charset=UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
  <channel>
    <title><?php echo $site_title; ?></title>
    <link><?php echo $site_url; ?></link>
    <description><?php echo $site_desc; ?></description>
    <image>
      <url><?php echo $logo_url; ?></url>
      <title><?php echo $site_title; ?> RSS feed</title>
      <link><?php echo $site_url; ?></link>
    </image>
    <language>en</language>
    <generator><?php echo $site_url; ?></generator>
    <lastBuildDate><?php echo date('r'); ?></lastBuildDate>
<?php foreach ($videos as $video) : ?>
<?php
    $vid       = (int) $video['VID'];
    $title     = rss_escape($video['title']);
    $link      = rss_escape($config['BASE_URL'] . '/video/' . $vid . '/' . prepare_string($video['title']));
    $timestamp = (int) $video['addtime'];
    if ($timestamp <= 0) {
        $timestamp = (int) strtotime((string) $video['adddate']);
    }
    $pubdate   = date('r', $timestamp > 0 ? $timestamp : time());
    $thumb     = get_thumb_url($vid) . '/' . (int) $video['thumb'] . '.jpg';
?>
    <item>
      <title><?php echo $title; ?></title>
      <guid isPermaLink="true"><?php echo $link; ?></guid>
      <pubDate><?php echo $pubdate; ?></pubDate>
      <link><?php echo $link; ?></link>
      <description><![CDATA[<img src="<?php echo $thumb; ?>" alt="<?php echo $title; ?>"><br>
<?php echo rss_cdata($video['description']); ?>]]></description>
      <dc:creator><?php echo $site_title; ?></dc:creator>
    </item>
<?php endforeach; ?>
  </channel>
</rss>
