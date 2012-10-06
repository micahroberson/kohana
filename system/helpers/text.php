<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Text helper class.
 *
 * $Id: text.php 3769 2008-12-15 00:48:56Z zombor $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class text_Core {

	/**
	 * Limits a phrase to a given number of words.
	 *
	 * @param   string   phrase to limit words of
	 * @param   integer  number of words to limit to
	 * @param   string   end character or entity
	 * @return  string
	 */
	public static function limit_words($str, $limit = 100, $end_char = NULL)
	{
		$limit = (int) $limit;
		$end_char = ($end_char === NULL) ? '&#8230;' : $end_char;

		if (trim($str) === '')
			return $str;

		if ($limit <= 0)
			return $end_char;

		preg_match('/^\s*+(?:\S++\s*+){1,'.$limit.'}/u', $str, $matches);

		// Only attach the end character if the matched string is shorter
		// than the starting string.
		return rtrim($matches[0]).(strlen($matches[0]) === strlen($str) ? '' : $end_char);
	}

	/**
	 * Limits a phrase to a given number of characters.
	 *
	 * @param   string   phrase to limit characters of
	 * @param   integer  number of characters to limit to
	 * @param   string   end character or entity
	 * @param   boolean  enable or disable the preservation of words while limiting
	 * @return  string
	 */
	public static function limit_chars($str, $limit = 100, $end_char = NULL, $preserve_words = FALSE)
	{
		$end_char = ($end_char === NULL) ? '&#8230;' : $end_char;

		$limit = (int) $limit;

		if (trim($str) === '' OR utf8::strlen($str) <= $limit)
			return $str;

		if ($limit <= 0)
			return $end_char;

		if ($preserve_words == FALSE)
		{
			return rtrim(utf8::substr($str, 0, $limit)).$end_char;
		}

		preg_match('/^.{'.($limit - 1).'}\S*/us', $str, $matches);

		return rtrim($matches[0]).(strlen($matches[0]) == strlen($str) ? '' : $end_char);
	}

	/**
	 * Alternates between two or more strings.
	 *
	 * @param   string  strings to alternate between
	 * @return  string
	 */
	public static function alternate()
	{
		static $i;

		if (func_num_args() === 0)
		{
			$i = 0;
			return '';
		}

		$args = func_get_args();
		return $args[($i++ % count($args))];
	}

	/**
	 * Generates a random string of a given type and length.
	 *
	 * @param   string   a type of pool, or a string of characters to use as the pool
	 * @param   integer  length of string to return
	 * @return  string
	 *
	 * @tutorial  alnum     alpha-numeric characters
	 * @tutorial  alpha     alphabetical characters
	 * @tutorial  hexdec    hexadecimal characters, 0-9 plus a-f
	 * @tutorial  numeric   digit characters, 0-9
	 * @tutorial  nozero    digit characters, 1-9
	 * @tutorial  distinct  clearly distinct alpha-numeric characters
	 */
	public static function random($type = 'alnum', $length = 8)
	{
		$utf8 = FALSE;

		switch ($type)
		{
			case 'alnum':
				$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
			case 'alpha':
				$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
			case 'hexdec':
				$pool = '0123456789abcdef';
			break;
			case 'numeric':
				$pool = '0123456789';
			break;
			case 'nozero':
				$pool = '123456789';
			break;
			case 'distinct':
				$pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
			break;
			default:
				$pool = (string) $type;
				$utf8 = ! utf8::is_ascii($pool);
			break;
		}

		// Split the pool into an array of characters
		$pool = ($utf8 === TRUE) ? utf8::str_split($pool, 1) : str_split($pool, 1);

		// Largest pool key
		$max = count($pool) - 1;

		$str = '';
		for ($i = 0; $i < $length; $i++)
		{
			// Select a random character from the pool and add it to the string
			$str .= $pool[mt_rand(0, $max)];
		}

		// Make sure alnum strings contain at least one letter and one digit
		if ($type === 'alnum' AND $length > 1)
		{
			if (ctype_alpha($str))
			{
				// Add a random digit
				$str[mt_rand(0, $length - 1)] = chr(mt_rand(48, 57));
			}
			elseif (ctype_digit($str))
			{
				// Add a random letter
				$str[mt_rand(0, $length - 1)] = chr(mt_rand(65, 90));
			}
		}

		return $str;
	}

  /**
   * Generate a standardized token for use across the site.  This
   * makes it easy to change the method used to generate random tokens.
   */
  public static function token() {
    return sha1(time() . text::random('alnum', 64));
  }

	/**
	 * Reduces multiple slashes in a string to single slashes.
	 *
	 * @param   string  string to reduce slashes of
	 * @return  string
	 */
	public static function reduce_slashes($str)
	{
		return preg_replace('#(?<!:)//+#', '/', $str);
	}

	/**
	 * Replaces the given words with a string.
	 *
	 * @param   string   phrase to replace words in
	 * @param   array    words to replace
	 * @param   string   replacement string
	 * @param   boolean  replace words across word boundries (space, period, etc)
	 * @return  string
	 */
	public static function censor($str, $badwords, $replacement = '#', $replace_partial_words = FALSE)
	{
		foreach ((array) $badwords as $key => $badword)
		{
			$badwords[$key] = str_replace('\*', '\S*?', preg_quote((string) $badword));
		}

		$regex = '('.implode('|', $badwords).')';

		if ($replace_partial_words == TRUE)
		{
			// Just using \b isn't sufficient when we need to replace a badword that already contains word boundaries itself
			$regex = '(?<=\b|\s|^)'.$regex.'(?=\b|\s|$)';
		}

		$regex = '!'.$regex.'!ui';

		if (utf8::strlen($replacement) == 1)
		{
			$regex .= 'e';
			return preg_replace($regex, 'str_repeat($replacement, utf8::strlen(\'$1\'))', $str);
		}

		return preg_replace($regex, $replacement, $str);
	}

	/**
	 * Finds the text that is similar between a set of words.
	 *
	 * @param   array   words to find similar text of
	 * @return  string
	 */
	public static function similar(array $words)
	{
		// First word is the word to match against
		$word = current($words);

		for ($i = 0, $max = strlen($word); $i < $max; ++$i)
		{
			foreach ($words as $w)
			{
				// Once a difference is found, break out of the loops
				if ( ! isset($w[$i]) OR $w[$i] !== $word[$i])
					break 2;
			}
		}

		// Return the similar text
		return substr($word, 0, $i);
	}

	/**
	 * Converts text email addresses and anchors into links.
	 *
	 * @param   string   text to auto link
	 * @return  string
	 */
	public static function auto_link($text)
	{
		// Auto link emails first to prevent problems with "www.domain.com@example.com"
		return text::auto_link_urls(text::auto_link_emails($text));
	}

	/**
	 * Converts text anchors into links.
	 *
	 * ##### Example
	 *
	 *     echo text::auto_link_urls('http://example.com');
	 *
	 *     // Output:
	 *     <a href="http://example.com">http://example.com</a>
	 *
	 * @param   string   $text  Text to auto link
	 * @return  string
	 */
	public static function auto_link_urls($text)
	{

		$regex = '~\\b'
				.'((?:ht|f)tps?://)?' // protocol
				.'(?:[-a-zA-Z0-9]{1,63}\.)+' // host name
				.'(?:aero|asia|biz|cat|com|coop|edu|gov|info|int|jobs|mil|mobi|museum|name|net|org|pro|tel|travel|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|ee|eg|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)' // tlds
				.'(?:/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?' // path
				.'(?:\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?' // query
				.'(?:#[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?' // fragment
				.'(?=[?.!,;:"]?(?:\s|$))~'; // punctuation and url end

		$result = "";
		$position = 0;

		while (preg_match($regex, $text, $match, PREG_OFFSET_CAPTURE, $position))
		{
			list($url, $url_pos) = $match[0];
			
			// Add the text before the url
			$result .= substr($text, $position, $url_pos - $position);
			
			// Only link urls when not between object tags.
      $escaped_url = preg_quote($url, '/');
      $object_regex = "/(<object(?!<\/object>).+)$escaped_url(.+(?!<object)<\/object>)/i";
      if (preg_match($object_regex, $text, $object_matches)) {
        $result .= $url;
      }
      else {
  			// Default to http://
  			$full_url = empty($match[1][0]) ? 'http://'.$url : $url;

  			// Add the hyperlink.
  			$result .= html::anchor($full_url, $url);
      }
      
      // New position to start parsing
      $position = $url_pos + strlen($url);
		}

		return $result.substr($text, $position);
	}

	/**
	 * Converts text email addresses into links.
	 *
	 * @param   string   text to auto link
	 * @return  string
	 */
	public static function auto_link_emails($text)
	{
		// Finds all email addresses that are not part of an existing html mailto anchor
		// Note: The "58;" negative lookbehind prevents matching of existing encoded html mailto anchors
		//       The html entity for a colon (:) is &#58; or &#058; or &#0058; etc.
		if (preg_match_all('~\b(?<!href="mailto:|">|58;)(?!\.)[-+_a-z0-9.]++(?<!\.)@(?![-.])[-a-z0-9.]+(?<!\.)\.[a-z]{2,6}\b~i', $text, $matches))
		{
			foreach ($matches[0] as $match)
			{
				// Replace each email with an encoded mailto
				$text = str_replace($match, html::mailto($match), $text);
			}
		}

		return $text;
	}

	/**
	 * Automatically applies <p> and <br /> markup to text. Basically nl2br() on steroids.
	 *
	 * @param   string   subject
	 * @return  string
	 */
	public static function auto_p($str)
	{
		// Trim whitespace
		if (($str = trim($str)) === '')
			return '';

		// Standardize newlines
		$str = str_replace(array("\r\n", "\r"), "\n", $str);

		// Trim whitespace on each line
		$str = preg_replace('~^[ \t]+~m', '', $str);
		$str = preg_replace('~[ \t]+$~m', '', $str);

		// The following regexes only need to be executed if the string contains html
		if ($html_found = (strpos($str, '<') !== FALSE))
		{
			// Elements that should not be surrounded by p tags
			$no_p = '(?:p|div|h[1-6r]|ul|ol|li|blockquote|d[dlt]|pre|t[dhr]|t(?:able|body|foot|head)|c(?:aption|olgroup)|form|s(?:elect|tyle)|a(?:ddress|rea)|ma(?:p|th))';

			// Put at least two linebreaks before and after $no_p elements
			$str = preg_replace('~^<'.$no_p.'[^>]*+>~im', "\n$0", $str);
			$str = preg_replace('~</'.$no_p.'\s*+>$~im', "$0\n", $str);
		}

		// Do the <p> magic!
		$str = '<p>'.trim($str).'</p>';
		$str = preg_replace('~\n{2,}~', "</p>\n\n<p>", $str);

		// The following regexes only need to be executed if the string contains html
		if ($html_found !== FALSE)
		{
			// Remove p tags around $no_p elements
			$str = preg_replace('~<p>(?=</?'.$no_p.'[^>]*+>)~i', '', $str);
			$str = preg_replace('~(</?'.$no_p.'[^>]*+>)</p>~i', '$1', $str);
		}

		// Convert single linebreaks to <br />
		$str = preg_replace('~(?<!\n)\n(?!\n)~', "<br />\n", $str);

		return $str;
	}

	/**
	 * Returns human readable sizes.
	 * @see  Based on original functions written by:
	 * @see  Aidan Lister: http://aidanlister.com/repos/v/function.size_readable.php
	 * @see  Quentin Zervaas: http://www.phpriot.com/d/code/strings/filesize-format/
	 *
	 * @param   integer  size in bytes
	 * @param   string   a definitive unit
	 * @param   string   the return string format
	 * @param   boolean  whether to use SI prefixes or IEC
	 * @return  string
	 */
	public static function bytes($bytes, $force_unit = NULL, $format = NULL, $si = TRUE)
	{
		// Format string
		$format = ($format === NULL) ? '%01.2f %s' : (string) $format;

		// IEC prefixes (binary)
		if ($si == FALSE OR strpos($force_unit, 'i') !== FALSE)
		{
			$units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
			$mod   = 1024;
		}
		// SI prefixes (decimal)
		else
		{
			$units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
			$mod   = 1000;
		}

		// Determine unit to use
		if (($power = array_search((string) $force_unit, $units)) === FALSE)
		{
			$power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;
		}

		return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
	}

  public static function percentage($value, $total = 100, $show_percent = TRUE) {
    $value = number_format(($value / $total * 100));
    return $show_percent ? $value .'%' : $value; 
  }

	/**
	 * Prevents widow words by inserting a non-breaking space between the last two words.
	 * @see  http://www.shauninman.com/archive/2006/08/22/widont_wordpress_plugin
	 *
	 * @param   string  string to remove widows from
	 * @return  string
	 */
	public static function widont($str)
	{
		$str = rtrim($str);
		$space = strrpos($str, ' ');

		if ($space !== FALSE)
		{
			$str = substr($str, 0, $space).'&nbsp;'.substr($str, $space + 1);
		}

		return $str;
	}
	
	/**
   * Pre index and search key filtering.
   * 
   * Removes any non alphanumeric characters, trims whitespace, and makes lowercase.
   *
   * @param   string    text to be processed
   * @return  string
   */
  public static function searchable($text) {
    $pattern = '/[^0-9a-zA-Z]*/i';
    return preg_replace($pattern, '', trim(strtolower($text)));
  }
  
  /**
   * Mask characters
   *
   * @param string The string to mask.
   * @param int  How many characters to display. Use negative to count from the front.
   * @return string  A masked string showing only x characters.
   */
  public static function mask($string, $count = 4) {
    $dir = $count > 0 ? STR_PAD_LEFT : STR_PAD_RIGHT;
    $output = str_pad(substr($string, -$count), strlen($string), '*', $dir);
    return $output;
  }
  
  /**
   * Converts a string to a suitable html ID attribute.
   *
   * http://www.w3.org/TR/html4/struct/global.html#h-7.5.2 specifies what makes a
   * valid ID attribute in HTML. This function:
   *
   * - Ensure an ID starts with an alpha character by optionally adding an 'id'.
   * - Replaces any character except alphanumeric characters with dashes.
   * - Converts entire string to lowercase.
   *
   * @source Drupal Zen Theme. http://drupal.org/project/zen
   *
   * @param $string
   *   The string
   * @return
   *   The converted string
   */
  public static function id_safe($string) {
    // Replace with dashes anything that isn't A-Z, numbers, dashes, or underscores.
    return strtolower(preg_replace('/[^a-zA-Z0-9-]+/i', '-', $string));
  }
  
  /**
   * Cleanse text input to a proper number.
   * 
   * Used primarily for stripping away number formatting and
   * dollar amount formatting. Removes everything but numbers
   * and periods. Could be improved to strip all but last period.
   */
  public static function number($input) {
    return preg_replace('/[^0-9\.]/i', '', $input);
  }
  
  /**
   * Return a minimum value.
   */
  public static function minimum($value, $minimum = 0) {
    return $value > 0 ? $value : $minimum;
  }
  
} // End text