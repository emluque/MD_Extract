<?php
/**
 * Simple example of How to Use the Class to get a JSON definition
 * 
 * @package mdx
 * @subpackage examples
 * @author Emiliano Martínez Luque ( http://www.metonymie.com)
 */
?><?php 

require_once("../md_extract/class.MD_Extract.php");
require_once("../md_extract/lang.errors.php");

$html = <<< HTML
<!DOCTYPE HTML>
<html>
 <head>
  <title>Photo gallery</title>
 </head>
 <body>

<div itemscope itemtype="http://data-vocabulary.org/Event">
  <a href="http://www.example.com/events/spinaltap" itemprop="url" >
    <span itemprop="summary">Spinal Tap</span>
  </a>
  Where:
  <span itemprop="location" itemscope itemtype="http://data-vocabulary.org/Organization">
     <span itemprop="name">Warfield Theatre</span>
     <span itemprop="address" itemscope itemtype="http://data-vocabulary.org/Address">
         <span itemprop="street-address">982 Market St</span>, 
         <span itemprop="locality">San Francisco</span>, 
         <span itemprop="region">CA</span>
     </span>
  </span>
  When:
  <time itemprop="startDate" datetime="2009-10-15T19:00-08:00">Oct 15, 7:00PM</time>
  <time itemprop="endDate" datetime="2009-10-15T21:00-08:00">9:00PM</time>
</div>

<div itemscope itemtype="http://data-vocabulary.org/Person"> 
  My name is <span itemprop="name">Bob Smith</span> 
  but people call me <span itemprop="nickname">Smithy</span>. 
  Here is my home page:
  <a href="http://www.example.com" itemprop="url">www.example.com</a>
  I live in Albuquerque, NM and work as an <span itemprop="title">engineer</span>
  at <span itemprop="affiliation">ACME Corp</span>.
</div>

<div itemscope itemtype="http://data-vocabulary.org/Product">
  <span itemprop="brand">ACME</span> <span itemprop="name">Executive
    Anvil</span>
  <img itemprop="image" src="anvil_executive.jpg" />

  <span itemprop="description">Sleeker than ACME's Classic Anvil, the 
    Executive Anvil is perfect for the business traveler
    looking for something to drop from a height.
  </span>
  Category: <span itemprop="category" content="Hardware > Tools > Anvils">Anvils</span>
  Product #: <span itemprop="identifier" content="mpn:925872">
    925872</span>
  <span itemprop="review" itemscope itemtype="http://data-vocabulary.org/Review-aggregate">
    <span itemprop="rating">4.4</span> stars, based on <span itemprop="count">89
      </span> reviews
  </span>

  <span itemprop="offerDetails" itemscope itemtype="http://data-vocabulary.org/Offer">
    Regular price: $179.99
    <meta itemprop="currency" content="USD" />
    $<span itemprop="price">119.99</span>
    (Sale ends <time itemprop="priceValidUntil" datetime="2010-11-05">
      5 November!</time>)
    Available from: <span itemprop="seller">Executive Objects</span>
    Condition: <span itemprop="condition" content="used">Previously owned, 
      in excellent condition</span>
    <span itemprop="availability" content="in_stock">In stock! Order now!</span>
  </span>
</div>

<div itemscope itemtype="http://data-vocabulary.org/Recipe" >
   <h1 itemprop="name">Grandma's Holiday Apple Pie</h1>
   <img src="apple-pie.jpg" itemprop="photo"/>
   By <span itemprop="author">Carol Smith</span>
   Published: <span itemprop="published" datetime="2009-11-05">November 5, 2009</span>
   <span itemprop="summary">This is my grandmother's apple pie recipe. I like to add a dash of nutmeg.</span>
   <span itemprop="review" itemscope itemtype="http://data-vocabulary.org/Review-aggregate">
      <span itemprop="rating">4.0</span> stars based on
      <span itemprop="count">35</span> reviews
   </span>
   Prep time: <time itemprop="prepTime" datetime="PT30M">30 min</time>
   Cook time: <time itemprop="cookTime" datetime="PT1H">1 hour</time>
   Total time: <time itemprop="totalTime" datetime="PT1H30M">1 hour 30 min</time>
   Yield: <span itemprop="yield">1 9" pie (8 servings)</span>
   <span itemprop="nutrition" itemscope itemtype="http://data-vocabulary.org/Nutrition">
      Serving size: <span itemprop="servingSize">1 medium slice</span>
      Calories per serving: <span itemprop="calories">250</span> 
      Fat per serving: <span itemprop="fat">12g</span>
   </span>
   Ingredients:
   <span itemprop="ingredient" itemscope itemtype="http://data-vocabulary.org/RecipeIngredient">
      Thinly-sliced <span itemprop="name">apples</span>:     
      <span itemprop="amount">6 cups</span>
   </span>
   <span itemprop="ingredient" itemscope itemtype="http://data-vocabulary.org/RecipeIngredient">
      <span itemprop="name">White sugar</span>:     
      <span itemprop="amount">3/4 cup</span>
   </span>
   ... 

   Directions:
   <div itemprop="instructions">
   1. Cut and peel apples
   2. Mix sugar and cinnamon. Use additional sugar for tart apples.
   ...
   </div>
</div>

<div itemscope itemtype="http://data-vocabulary.org/Review">
  <span itemprop="itemreviewed" itemscope itemtype="http://data-vocabulary.org/Organization">
    <span itemprop="name">L™Amourita Pizza</span>
    Located at 
    <span itemprop="address" itemscope itemtype="http://data-vocabulary.org/Address">
      <span itemprop="street-address">123 Main St</span>, 
      <span itemprop="locality">Albuquerque</span>, 
      <span itemprop="region">NM</span>.
    </span>
    <a href="http://pizza.example.com" itemprop="url">http://pizza.example.com</a>
  </span>
  Reviewed by <span itemprop="reviewer">Bob Smith</span>. Rated: 
  <span itemprop="rating" itemscope itemtype="http://data-vocabulary.org/Rating">
    <span itemprop="value">9</span>/
    <span itemprop="best">10</span> (Excellent)
  </span>
</div>

  
 </body>
</html>
HTML;



$mdx = MD_Extract::create_by_html($html, "http://www.example.com");

echo("<!DOCTYPE HTML>
<html>
 <head>
  <title>Example</title>
 </head>
 <body>
  <h1>Original HTML:</h1>
<code><pre>
" . htmlentities($html) . "
</pre></code>
  <h1>Results:</h1>
 <pre>");
var_dump($mdx->get_clean_results());
echo("</pre><h1>Errors</h1><pre>");
var_dump($mdx->get_errors());
echo("</pre></body></html>");


?>
