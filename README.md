# HoursKeeper-smrsh

I use <a href="https://itunes.apple.com/us/app/hours-keeper-time-tracking/id563155321?mt=8" target=_blank>HoursKeeper</a> to keep my timesheet.  While HourseKeeper does basic reporting, and allows you to back-up data and issues invoices, I had a need to do more and different kinds of reporting for specific clients.  Indeed, I built an entire portal where clients can review my timesheets and pay on-line.  I also did not want to be tied to this app long term.  So I needed my timesheet data stored in a MySQL database on the server.

Luckily, HoursKeeper allows you to export your timesheet data to a CSV which it will then e-mail to any address as an attachment.

Since I run my own mail server (sendmail) I setup a special e-mail address to receive these CSV timesheets, parse them and insert them into a database.

With sendmail this can be accomplished with <a href="http://www.tldp.org/LDP/solrhe/Securing-Optimizing-Linux-RH-Edition-v1.3/chap22sec182.html" target=_blank>smrsh</a>.  In a virtusertable you will pipe the designated recipient to a smrsh script which will be automatically called each time this address receives an e-mail.  

Here is my MySQL database structure:

<pre>
CREATE TABLE `timesheet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `client` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `worked` int(11) NOT NULL,
  `rate` double(7,2) DEFAULT NULL,
  `amount` double(7,2) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timesheet_inserted` (`inserted`),
  KEY `timesheet_client` (`client`),
  KEY `timesheet_date` (`date`),
  KEY `timesheet_note` (`note`),
  KEY `timesheet_label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
</pre>
