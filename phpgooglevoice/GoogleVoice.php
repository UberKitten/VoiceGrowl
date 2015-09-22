<?php

require_once 'simple_html_dom.php'; // Handy DOM manipulation class

/*
*
* Copyright (c) 2009 Astra West
*
* Permission is hereby granted, free of charge, to any person
* obtaining a copy of this software and associated documentation
* files (the "Software"), to deal in the Software without
* restriction, including without limitation the rights to use,
* copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the
* Software is furnished to do so, subject to the following
* conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
* OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
* HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
* WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
* FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
* OTHER DEALINGS IN THE SOFTWARE.
* 
*/

if (!function_exists('array_diff_recursive')) // From a PHP.net comment on array_diff
{
    function array_diff_recursive($aArray1, $aArray2) {
        $aReturn = array();
       
        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = array_diff_recursive($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }
       
        return $aReturn;
    }
}

class GoogleVoice
{
    private $Username, $Password;
    private $ch;
    private $page = null; // Main inbox only
    private $_rnr_se = null;
    public $logged_in = false;
    
    /**
    * Set up cURL stuff
    * 
    * @param mixed $Username Can be username or email
    * @param mixed $Password 
    * @return GoogleVoice
    */
    function __construct($Username, $Password)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Setting to true screws everything up
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // May not be right for everyone, but fast enough for me
        
        curl_setopt($ch, CURLOPT_REFERER, 'https://www.google.com/voice/m');
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHPGoogleVoice/1.0 (phpgooglevoice@ub3rk1tten.com)');
        
        if (strpos($Username, '@')) // Email address
        {
            $temp = explode('@', $Username);
            $folder = $temp[1];
        }
        else
            $folder = 'gmail.com';
            
        $cookiefile = './cookies/' . $folder . '/' . $Username . '.txt';
        
        if (!file_exists('./cookies/' . $folder . '/'))
            mkdir('./cookies/' . $folder . '/', null, true);
            
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
        
        $this->ch = $ch;
        $this->Username = $Username;
        $this->Password = $Password;
    }
    
    /**
    * I think cURL saves cookies when closed...
    */
    function __destruct()
    {
        curl_close($this->ch);
    }
    
    /**
    * Authenticate with Google Voice (mobile)
    * 
    * Takes about 2.2 seconds for me to log in, it's 3 faster not following Location: headers
    * 
    * @return boolean Whether the login succeeded or not
    */
    function login()
    {
        if ($this->Username == '' || $this->Password == '')
            return false;
        
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, false);
        
        curl_setopt($this->ch, CURLOPT_URL, 'http://www.google.com/accounts/ServiceLoginAuth?service=grandcentral');
        curl_setopt($this->ch, CURLOPT_POST, true);
        $post = array(
            'tmpl' => 'mobile',
            'continue' => 'https://www.google.com/voice/account/signin/?prev=%2Fm',
            'service' => 'grandcentral',
            'ltmpl' => 'mobile',
            'btmpl' => 'mobile',
            'GALX' => 'dBBq3VuAV5Y', // Changes, need to investigate
            'Email' => $this->Username,
            'Passwd' => $this->Password,
            'PersistentCookie' => 'true',
            'rmShown' => 1,
            'signIn' => 'Sign in'
        );
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post);
        
        $ret = curl_exec($this->ch);
        
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        
        if (stristr($ret, 'username or password you entered is incorrect') ||
            stristr($ret, 'field must not be left blank'))
            return false;
        return true;
    }
    
    /**
    * Loads a new mobile inbox page into $this->page.
    * Returns it as well.
    * $new_page in other functions is just so you don't have to call load_page if true.
    * They don't provide for pagination like this does (for simplicity)
    * This will silently fail for invalid pages, including 0.
    * 
    * @param mixed $pagination The inbox pagination page to load
    */
    function load_page($pagination = 1)
    {
        curl_setopt($this->ch, CURLOPT_POST, false);
        curl_setopt($this->ch, CURLOPT_URL, 'https://www.google.com/voice/m/i?p=' . $pagination);
        $this->page = curl_exec($this->ch);
        $this->page = str_get_html($this->page);
        return $this->page;
    }
     
    /**
    * Simplify getting the current inbox.
    * Fetches a new page if there is none.
    *    
    * @param mixed $new_page Whether to get a new page
    * @return simple_html_dom The page class
    */
    function get_page($new_page)
    {
        if ($new_page || $this->page === null)
            return $this->load_page();
        return $this->page;
    }
    
    /**
    * Parses the mobile inbox in $this->page and tries to get the messages.
    * 99% guaranteed to break when Google changes something or your money back!
    * 
    * Uses the mobile version because
    * 1) Google doesn't change it that often
    * 2) Features will start in the full client and migrate to mobile, leaving plenty of time to be ready
    * 3) Faster for us
    * 4) Less bandwidth usage for Google
    * 5) Easy to parse
    * 
    * Downside is that we only get some of the messages. Pagination can solve that.
    * The main GV site does provide the inbox as XML, but it's ernormous (108KB).
    * 
    * Array looks like this:
    * Array (
    *   [0] => Array ( // Threaded Conversations
    *       'type' 'Text_message' or 'Voicemail',
    *       'id' Google's internal id, used for performing actions
    *       'read' boolean
    *       'contact_id'
    *       'time' Google's relative representation of time
    *       'name' Or their number, nicely formatted
    *       'number' As seen in the message header, but not nicely formatted
    *       'messages' Array ( // Messages in the conversation
    *           [0] => Array (
    *               'name' => The name of the sender (or 'Me')
    *               'message' => The message text
    *           );
    *       );
    * );     
    * 
    * @param $new_only Return new messages (true) or all? (false)
    * @param $new_page Always get a new page
    */
    function get_messages($new_only, $new_page = false)
    {        
        $html = $this->get_page($new_page);
        $messages = array();
        
        foreach($html->find('div') as $element)
        {
            $go = false;
            if ($new_only)
                if ($element->class == 'mu')
                    $go = true;
            if (!$new_only)
                if ($element->class == 'mr' || $element->class == 'mu')
                    $go = true;
                    
            if ($go)
            // if new only, is it mu? or if not new_only, is it mr or mu?
            {
                $new = array(); // The threaded conversation
                $new['type'] = $element->children[0]->children[0]->children[0]->alt; // div, span, img
                $new['id'] = $element->id;
                if ($element->class == 'mu')
                    $new['read'] = false;
                if ($element->class == 'mr')
                    $new['read'] = true;
                
                if (isset($element->children[0]->children[1]->children[0]->href))
                {
                    $new['contact_id'] = $element->children[0]->children[1]->children[0]->href; // div, b, a
                    $new['contact_id'] = substr($new['contact_id'], strrpos($new['contact_id'], '/') + 1); // end part of url is ID
                }
                
                if (array_key_exists(0,$element->children[0]->children[1]->children))
                    $new['name'] = $element->children[0]->children[1]->children[0]->innertext;
                
                $new['time'] = trim($element->children[0]->children[2]->innertext, '()'); // div, span
                
                
                $new['number'] = $element->children[0]->children[3]->href;
                $new['number'] = substr($new['number'], strrpos($new['number'], '=') + 1); // end part of url is number
                
                if ($new['type'] == 'Text_message')
                {
                    foreach($element->children[1]->children as &$child) 
                    {
                        if ($child->class == '')
                        {
                            $name = $child->children[0]->children[0]->innertext; // span, b
                            $message = $child->children[1]->innertext;
                            $time = $child->children[2]->innertext;
                            
                            $new['messages'][] = array(
                                'name' => html_entity_decode(rtrim(trim($name), ':'), ENT_QUOTES),
                                'message' => html_entity_decode(trim($message), ENT_QUOTES),
                                'time' => trim($time, ' ()')
                            );
                        }
                    }
                }
                elseif ($new['type'] == 'Voicemail')
                {
                    $name = trim($element->children[0]->children[1]->children[0]->innertext);
                    $message = '';
                    foreach($element->children[1]->children as &$part)
                    {
                        if ($part->class != 'ms2')
                            $message .= $part->innertext . ' ';
                    }
                    $new['messages'][] = array(
                        'name' => html_entity_decode(rtrim(trim($name), ':'), ENT_QUOTES),
                        'message' => html_entity_decode(trim($message), ENT_QUOTES)
                    );
                }
                
                $messages[] = $new;
            }
        }
        return $messages;
    }
    
    /**
    * Returns all unread messages until it hits a read one.
    * Will continue across multiple pages.
    * If you plan on calling a function that processes $this->page after this,
    * you need to set $new_page for it or load_page(0)
    * 
    * @param boolean $new_page
    */
    function get_all_new_messages($new_page = false)
    {
        $i = 1;
        $ret = array();
        $go = true;
        
        while($go)
        {
            if ($i == 1)
            {
                $messages = $this->get_messages(false, $new_page);
            }
            else
            {
                $this->load_page($i);
                $messages = $this->get_messages(false);
            }
            
            foreach($messages as &$message)
            {
                if ($message['read'] == false)
                    $ret[] = $message;
                else
                    return $ret;
            }
            
            $i++;
        }
    }
    
    
    /**
    * Gets the unread/read message count
    * 
    * @param mixed $new_page Always get a new page
    * @return array Associative array with 'unread' [0] and 'read' [1]
    */
    function message_count($new_page = false)
    {
        $html = $this->get_page($new_page);
        $unread = 0;
        $read = 0;
        foreach($html->find('div') as $element)
        {
            if ($element->class == 'mu')    
                $unread++;
            if ($element->class == 'mr')
                $read++;
        }
        return array('unread' => $unread, 'read' => $read);
    }
    
    /**
    * Marks the given conversation id as read or not read.
    * 
    * @param string $id
    * @param boolean $read Read or not read
    */
    function mark_as($id, $read)
    {
        curl_setopt($this->ch, CURLOPT_POST, false);
        curl_setopt($this->ch, CURLOPT_URL, 'https://www.google.com/voice/m/mark?p=1&label=inbox&id=' . urlencode($id) . '&read=' . $read);   
        curl_exec($this->ch);
    }
    
    /**
    * Archives the given conversation id
    * 
    * @param string $id
    */
    function archive($id)
    {
        curl_setopt($this->ch, CURLOPT_POST, false);
        curl_setopt($this->ch, CURLOPT_URL, 'https://www.google.com/voice/m/archive?p=1&label=inbox&id=' . urlencode($id));   
        curl_exec($this->ch);
    }
    
    /**
    * Gets the given contact id's numbers (and other useful things GV gives us) in an array, ready to send to in a POST
    * 
    * @param string $id Contact ID
    */
    function get_contact_numbers($id)
    {
        curl_setopt($this->ch, CURLOPT_POST, false);
        curl_setopt($this->ch, CURLOPT_URL, 'https://www.google.com/voice/m/contact/' . $id);   
        $html = curl_exec($this->ch);
        $html = str_get_html($html);
        
        $ret = array();
        
        foreach ($html->find('form') as $form)
        {
            if ($form->class == 'c14')
            {
                $new = array();
                
                $new['name'] = $form->children[0]->value;
                $new['number'] = $form->children[1]->value;
                $new['_rnr_se'] = $form->children[2]->value;
                $new['type'] = trim($form->children[3]->children[0]->plaintext, '()');
            
                $ret[] = $new;
            }
        }
        return $ret;
    }
    
    /**
    * Gets the user's Google Voice number.
    * It's preferable to not strip formatting when showing this to the user, because then it
    *  keeps Google's format, and prevents any UI discontinuities.
    * 
    * @param boolean $remove_formatting Whether to strip formatting
    * @param boolean $new_page
    * @return The number or false
    */
    function google_number($remove_formatting, $new_page = false)
    {
        $html = $this->get_page($new_page);
        
        foreach($html->find('b') as $element)
            if ($element->class == 'ms3')
                if ($remove_formatting)
                    return preg_replace('/[^0-9]/', '', $element->innertext); // Anything not a number
                else
                    return $element->innertext;
        
        return false;
    }
    
    /**
    * _rnr_se's stay the same in a session... Or per user?
    * If $new_page, will ignore the _rnr_se we have.
    * Otheriwse, will return it.
    * 
    * @param mixed $new_page
    * @return string The _rnr_se
    */
    function _rnr_se($new_page = false)
    {
        if ($new_page || $this->_rnr_se == null)
        {
            $html = $this->get_page($new_page);
            foreach($html->find('input') as $element)
                if ($element->name == '_rnr_se')
                    $this->_rnr_se = $element->value;
        }
        
        return $this->_rnr_se;
                
        /*
            Old ReGeX
            Will be deleted next commit
        // Get the Google _rnr_se security token
        curl_setopt($this->ch, CURLOPT_POST, false);
        curl_setopt($this->ch, CURLOPT_URL, 'https://www.google.com/voice/m');   
        $ret = curl_exec($this->ch);
        preg_match('/"_rnr_se" value="\/[+=0-9a-zA-Z]*"\//', $ret, $_rnr_se); // Could use simple_html_dom to do this, but that's overkill
        $_rnr_se = substr($_rnr_se[0], 17, count($_rnr_se[0]) - 3);
        */
    }
    
    /**
    * Calls the given number.
    * Google Voice will accept invalid numbers for $number, so no sense returning if successful.
    * 
    * @param mixed $number
    * @param string $from Which phone number Google will call before connecting
    */
    function call_number($number, $from = '')
    {
        curl_setopt($this->ch, CURLOPT_URL, 'https://www.google.com/voice/m/sendcall');  
        curl_setopt($this->ch, CURLOPT_POST, true); 
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, array(
            '_rnr_se' => $this->_rnr_se(),
            'number' => $number,
            'phone' => $from,
            'call' => 'Call'
        ));
        // Refresh the page
        $this->page = str_get_html(curl_exec($this->ch));
    }
    
    /**
    * Send an sms to the given number (no contacts, I tried).
    * Google has 'id' and 'c' hidden form variables in here, value 'undefined'...
    * Also, inconsistencies between calling and smsing in terms of the submit name/value.
    * 
    * @param mixed $number
    * @param mixed $message
    */
    function send_sms($number, $message)
    {
        curl_setopt($this->ch, CURLOPT_URL, 'https://www.google.com/voice/m/sendsms');  
        curl_setopt($this->ch, CURLOPT_POST, true); 
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, array(
            '_rnr_se' => $this->_rnr_se(),
            'number' => $number,
            'smstext' => urlencode($message),
            'id' => '',
            'c' => ''
        ));
        // Refresh the page
        $this->page = str_get_html(curl_exec($this->ch));
    }
    
    /**
    * Get the available phones and whether they're forwarding or not
    * 
    * @return array 2d array of array of 'forwarding' [0] and 'number' [1]
    */
    function get_phones()
    {
        curl_setopt($this->ch, CURLOPT_POST, false);
        curl_setopt($this->ch, CURLOPT_URL, 'https://www.google.com/voice/m/phones');   
        $html = curl_exec($this->ch);
        
        $names = array();
        preg_match_all('/\/>[\r\n ]*[A-Za-z0-9(): +\-]*<br \/>/', $html, $names);
        print_r($names);
        
        $html = str_get_html($html);
        
        $ret = array();
        $i = 0;
        
        foreach ($html->find('input') as $element)
        {
            if ($element->type == 'checkbox')
            {
                $fwd = ($element->checked == '1');
                $phone = substr($names[0][$i], 2, strlen($names[0][$i]) - 8); // Cut off leading /> and ending <br />
                $phone = explode(':', trim($phone));
                $phone[0] = ltrim($phone[0]);
                $phone[1] = str_replace(array('(', ')', ' ', '-', "\n", "\r"), '', $phone[1]);
                
                $ret[] = array('forwarding' => $fwd, 'number' => $phone);
                $i++;
            }
        }
        return $ret;
    }
    
    /**
    * Searches the contacts for $search, using Google's functions, and returns what it gets.
    * Empty array for no results.
    * 
    * @return array 2d array of array( 'name' [0], 'id' [1])
    */
    function search_contacts($search)
    {
        curl_setopt($this->ch, CURLOPT_POST, false);
        curl_setopt($this->ch, CURLOPT_URL, 'https://www.google.com/voice/m/contacts?q=' . urlencode($search));   
        $html = curl_exec($this->ch);
        $html = str_get_html($html);
        
        $ret = array();
        
        foreach($html->find('a') as $element)
        {
            if (substr($element->href, 0, strrpos($element->href, '/') + 1) == '/voice/m/contact/')
            {
                $ret[] = array('name' => $element->plaintext, 'id' => substr($element->href, strrpos($element->href, '/') + 1));   
            }
        }
        return $ret;
    }
    
    /**
    * Returns a get_messages() style array of new or updated conversations in $message1
    * In other words, any messages in $message1 that are not in $message2 are returned
    * 
    * @param mixed $message1 The newer messages
    * @param mixed $message2 The older messages
    */
    static function diff_messages($message1, $message2)
    {
        $new = array(); // Hold return value
        foreach($message1 as $first) // Get changed conversations
        {
            $newconv = true;
            foreach($message2 as $second)
            {
                if ($first['id'] == $second['id'])
                {
                    $newconv = false;
                    $messages = array_diff_recursive($first['messages'], $second['messages']);
                    if (count($messages) != 0)
                    {
                        $new2 = $first; // Copy
                        $new2['messages'] = $messages; // Overwrite
                        $new[] = $new2;
                    }
                }
            }
            if ($newconv)
                $new[] = $first;
        }
        return $new;
    }
    
    /**
    * Joins two array of conversations together, preserving all messages but duplicating none.
    * This relies on accurate keys for the messages
    * 
    * @param mixed $message1
    * @param mixed $message2
    */
    static function join_messages($message1, $message2)
    {        
        $ret = array();
        foreach($message1 as $first)
        {
            $newconv = true;
            foreach($message2 as $second)
            {
                if ($first['id'] == $second['id']) // Same conversation, maybe different messages
                {
                    $newconv = false;
                    $newmsgs = array();
                    
                    foreach($first['messages'] as $key => $val)
                    {
                        $newmsgs[$key] = $val;
                    }
                    
                    foreach($second['messages'] as $key => $val)
                    {
                        $newmsgs[$key] = $val;
                    }
                    
                    $first['messages'] = $newmsgs;
                    $ret[] = $first;
                }
            }
            if ($newconv)
                $ret[] = $first;        
        }
        return $ret;
    }
}

?>
