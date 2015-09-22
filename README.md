# VoiceGrowl
## Several projects from 2009-2010 interfacing with Google Voice

Google Voice is a service that provides you one phone number for calls and SMS messages and forwards them to your other phones and devices. Originally the service was fairly bare and there was no mobile client for iOS. There were third-party clients and a mobile website, but none of them had push notifications. You had to receive an actual SMS, and this was back in the good old days when SMS plans were expensive and data was cheap.

One feature GV had was that it could send your GMail account an email every time you received an SMS message. I thought that data-only messaging was the way of the future. To bridge the gap in push notifications, I created VoiceGrowl. Users logged in using OAuth and configured their GMail to forward messages to my custom SMTP server. The service then extracted the message out of the email and forwarded the message to multiple user-configured services, such as the iOS app Prowl, the Windows/Mac notification app Growl, and custom HTTP push URLs. When you opened the notification on iOS, your third-party client of choice was opened and you could reply, all using data.

The service worked great and took only a few seconds end-to-end for new messages to appear. The project was [featured on Lifehacker](http://lifehacker.com/5360915/google-voice-growl-pushes-sms-alerts-to-your-iphone) and several thousand users used it at its peak. Later, Google released an official iOS with push notifications. I closed the service down around this time.

# PHPGoogleVoice

A PHP library/API to scrape and interact with the Google Voice website. The code was originally lost as the Facebook + Joyent free code repositories all disappeared, but I was able to find copies on the [Wayback Machine](https://web.archive.org/web/20100701080738/http://botsfordcr.facebook.joyent.us/svn/PHPGoogleVoice/). No docs though.

# SMS over Email
Was going to be hosted POP and SMTP servers developed in Python to interface with Google Voice (provides Google Voice messages in a POP format, allowing any email client to access your inbox like text messages were emails), until they released email forwarding and it was no longer needed. Unfortunately code has also been lost. POP was 90% finished, SMTP 75%. A very fun introduction for me to POP and SMTP, created almost entirely by reading RFCs.
