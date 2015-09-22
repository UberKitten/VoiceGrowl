<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
 <head>
  <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.7.0/build/reset/reset-min.css" /> 
  <link rel="stylesheet" type="text/css" href="http://fi3.fi/csstablegallery.css" />
  <style>
    html, body {
       background: rgb(187, 204, 255);
       margin: 0;
       text-align: center;
    }

    #container {
       width: 750px;
       margin: 25px auto 25px auto;
       text-align: left;
       background: #FFFFFF;
       border-right: 1px solid #CCCCCC;
       border-left: 1px solid #CCCCCC;
    }

    #content {
       padding: 10px 10px 10px 10px;
    }

    h1 {
       font-size: 35px;
       text-align: center;
    }

    h2 {
       font-size: 20px;
       text-align: center;
    }

    h3 {
       font-size: 16px;
       text-align: center;
    }

    object, embed {
       padding: 5px 5px 5px 5px;
    }

    strong {
       font-weight: bold;
    }

    table {
       width: 100%
    }
  </style>
  <title>T3h Ub3r K1tten's Google Voice Controlling via SMS or Email thing!</title>
 </head>
 <body>
  <div id="container">
   <div id="content">
    <h1>Controlling <img src="https://www.google.com/voice/resources/4232368305-voice_logo_sm.gif" /> via SMS or Email!</h1>
    <h2>Now with less SMS!</h2>
   </div>
  </div>

<?php 

if (isset($_GET['thanks']))
{ ?>
  <div id="container">
   <div id="content">
    <h1>Thanks for registering!
    <h2>Comments or suggestions?</h2>
    <h3>
     <a href="mailto:googlevoice@ub3rk1tten.com">Email me</a> or leave a message below.<br />
<object type="application/x-shockwave-flash" data="https://clients4.google.com/voice/embed/webCallButton" width="230" height="85"><param name="movie" value="https://clients4.google.com/voice/embed/webCallButton" /><param name="wmode" value="transparent" /><param name="FlashVars" value="id=6e893887087d7f5f74bd8a3601e3451a62845472&style=0" /></object>
    </h3>
   </div>
  </div>
<?php
}
?>

  <div id="container">
   <div id="content">
    <p>
     This service allows you to use emails to control your Google Voice account. And because a lot of carriers let you send SMS messages to email addresses, it's SMS too! You don't need a <a href="http://gizmodo.com/5324268/apple-rejects-official-google-voice-iphone-app">smartphone</a> or <a href="https://www.google.com/voice/m">data plan</a> to use all of Google Voice!
    </p>
   </div>
  </div>

  <div id="container">
   <div id="content">
    <p>
     <strong>Note:</strong> Google Voice will call your phone specified <a href="https://www.google.com/voice/m/selectphone">here</a>, so set it!<br />
     All of these commands are case insensitive, and any names will be searched and the first result used.<br />
     <br />
     <table id="commands">
      <thead>
       <tr>
        <th>Command</th>
        <th>Short code</th>
        <th>Example</th>
       </tr>
      </thead>
      <tbody>
       <tr>
        <td>Call</td>
        <td>c</td>
        <td>call john doe<br />
            c +1 650-253-0000</td>
       </tr>
       <tr>
        <td>SMS</td>
        <td>s</td>
        <td>sms leeroy LEEROY JENKINS?<br />
            sms "leeroy ferguson" Sorry, boss, wrong person...<br />
            s 6502530000 Awesome!<br />
            s "+1 650 253 0000" Note the quotes!</td>
       </tr>
      </tbody>
     </table>
    </p>
   </div>
  </div>
 </body>
</html>