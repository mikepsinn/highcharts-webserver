##Run a highcharts graph generation server

For quick start, spin up a Cent OS 6.5 instance and install the correct puppet configs from here: https://github.com/joegreen0991/puppet/tree/master/puppet using the following bootstrap script in your home directory

~~~
bash -c "$(curl -fsSL http://git.io/1qTpDw)" -s highcharts
~~~

Install this PHP web application to handle requests and pass to phantomjs/highcharts

~~~BASH
git clone https://github.com/joegreen0991/highcharts-webserver /srv/web/highcharts-webserver
/srv/web/highcharts-webserver/composer.phar install --working-dir /srv/web/highcharts-webserver

#Create the output directory with correct permissions
mkdir /srv/web/highcharts-webserver/public/charts
chmod a+w /srv/web/highcharts-webserver/public/charts
~~~

Then run the following command to download the highcharts files from here https://github.com/highslide-software/highcharts.com/tree/master/exporting-server/phantomjs

~~~BASH
git clone https://github.com/highslide-software/highcharts.com /srv/highcharts
~~~

And away you go!

POST a request at your new server with the correct parameters:

~~~

~~~

##Description of URL parameters

**infile**: The highcharts JSON configuration to convert. The script will try to fix unquoted keys and single quotes, which may have undesired effects. See `useraw` below.

**useraw**: Force the script to use the JSON as given, without trying to correct incorrect quotes.

**scale**: Default 2.5. To set the zoomFactor of the page rendered by PhantomJS. For example, if the chart.width option in the chart configuration is set to 600 and the scale is set to 2, the output raster image will have a pixel width of 1200. So this is a convenient way of increasing the resolution without decreasing the font size and line widths in the chart. This is ignored if the width parameter is set.

**width**: Set the exact pixel width of the exported image or pdf. This overrides the scale parameter.

**constr**: Default Chart. The constructor name. Can be one of Chart or StockChart. This depends on whether you want to generate Highstock or basic Highcharts.

**callback**: The callback is a function which will be called in the constructor of Highcharts to be executed on chart load. All code of the callback must be enclosed by a function. See this example of contents of the callback parameter:
