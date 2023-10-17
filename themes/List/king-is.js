var container = document.querySelector('#container');
var msnry;
// initialize Masonry after all images have loaded
imagesLoaded( container, function() {
  msnry = new Masonry( container, {
		itemSelector : '.box',
		gutter: 14
    });
	$('#loading').hide();
});

    var ias = $.ias({
      container: "#container",
      item: ".box",
      pagination: ".king-page-links-list",
      next: ".king-page-next",
      delay: 0,
	  negativeMargin: 200
    });

    ias.on('render', function(items) {
      $(items).css({ opacity: 0 });
    });

    ias.on('rendered', function(items) {
	imagesLoaded( container, function() {
	msnry.appended(items);
	});
	});
	
	ias.extension(new IASSpinnerExtension());