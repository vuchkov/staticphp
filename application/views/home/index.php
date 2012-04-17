<h1>This is and example todo project built on <a href="http://github.com/gintsmurans/staticphpc" target="_blank">StaticPHP framework</a></h1>


<div class="content">


  <div class="items">
    <div id="items">
      <?php foreach ($data['items'] as $key => $item): ?>
        <div class="item" data-id="<?php echo $key; ?>"><input type="checkbox" class="checkbox" /> <span><?php echo $item['title']; ?></span></div>
      <?php endforeach; ?>
    </div>
    
    <div id="add_item">
      <input type="text" id="add_item_input" /> <input type="button" id="add_item_submit" value="Add" />
    </div>

    <?php foreach ($data['items_done'] as $key => $item): ?>
      <div class="item done" data-id="<?php echo $key; ?>"><span><?php echo $item['title']; ?></span></div>
    <?php endforeach; ?>
  </div>
  
  <br />
  <p>
    * This thing currently works only per session and is saved in memcached - not to be used in production :)<br />
    * Demo limit: 400 items per session<br />
    * View source on the <a href="https://github.com/gintsmurans/staticphp/tree/example">GitHub</a>
  </p>



  <br />
  <pre><?php echo \load::execution_time(); ?></pre>
</div>