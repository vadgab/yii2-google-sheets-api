<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii2 Google Sheets Api Extension</h1>
    <br>
</p>




Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/):

```
composer require --prefer-dist vadgab/yii2-google-calendar-api
```

Basic information
-----------

This application operates with Google Calendar Service Account-based  authentication, meaning the login process happens in the background. It  enables querying, creating, modifying, and deleting calendar-related  events for third-party users without the need for authentication in a  pop-up window.

## Configuration

You will need to create a Service Account in the Google Cloud Console,  to which you must grant the appropriate permissions to access the calendar, as well as a authentication key, which you download and place  for the application to access, and provide the path to it.


##### 
