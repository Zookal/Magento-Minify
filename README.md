# WBL_Minify

- Zookal Version works only with Java/YUICompressor or old JSMin.
- Create versioned directories
- Less feature removed as not necessary
- Code optimized

#### @todo

- Maybe later a cURL API call to Googles Closure Compiler service: [http://closure-compiler.appspot.com/home](http://closure-compiler.appspot.com/home)
- use JShrink instead of JSMin as JSMin is deprecated
- Maybe only use Java or API call to Google because these libs generate the smallest optimized JavaScript.
- Add Node.js uglify

WBL_Minify extension enables minification of magento css merged files and/or  javascript merged files.
You can choose to use YUICompressor (included). In that case, be sure to have Java installed on your server and MAGE_ROOT_DIR/lib/YUICompressor.(-version-).jar executable.
Or you may prefer PHP Minifying classes (included).

PHP minifying libraries (Minify_Css_Compressor , JSMin) are taken from Stephen Clay's Minify project - http://code.google.com/p/minify/

## Facts

|   Total Size 	  | frontend js  | frontend css | backend js   |  backend css |
|:---------------:|-------------:|-------------:|-------------:|-------------:|
| no minification |  359.6 KB	 | 105.9 KB	|   627.1 KB   |   107.6 KB   |
| YUICompressor   |  205.7 KB  	 |  85.9 KB	|   340.9 KB   |    80.4 KB   |
| php classes     |  255.1 KB	 |  86.3 KB 	|   413.5 KB   |    81.2 KB   |

## Behaviour

This extension simply minifies css and javascript content before merged files are saved as in the normal magento behaviour.
No cache proxy server, or anything complicated : the simple genuine js and css magento browser cache behaviour, but with minified files ;)

## Installation

Use modman or composer please.

### Download package manually:

* Download latest version [here](https://github.com)
* Unzip in Magento root folder
* Clean cache

Log-out then Log-in in magento backend, go to System > Configuration > Developer > Minification Settings.
Then flush media/js and media/css files... and that's it !


## *NEW* Grouping files functionality

It is advised to disable merging in Magento because the seeming performance benifits aren't as real
as they seem. See the excellent Fishpig article:
[Why You Shouldn't Merge JavaScript in Magento](http://fishpig.co.uk/blog/why-you-shouldnt-merge-javascript-in-magento.html)

### How does it work?

The js and css files are normally combined to one large file, with this you can group them in
relevant groups (the product page gets its own group for example). More examples:

```xml
<layout>
	<!-- we add a group specifically for each locale, when customers are switching a language the whole css doesn't need to be reloaded -->
    <default>
        <reference name="head">
            <action method="addItem"><type>skin_css</type><stylesheet>css/responsive.css</stylesheet><params/><if/><cond/><group>locale</group></action>
            <!-- not the the <params/>, <if/> and the <cond/>, those are required. -->
        </reference>
    </default>

    <!-- on the product page we include the js in a different group (given the same name as the handle) -->
    <catalog_product_view>
        <reference name="head">
            <action method="addJs"><script>varien/product.js</script><params/><group>catalog_product_view</group></action>
            <!-- addCss works the same, addCssIe and addJsIe work the same -->
        </reference>
    </catalog_product_view>
</layout>
```

## License

OSL-3.0
