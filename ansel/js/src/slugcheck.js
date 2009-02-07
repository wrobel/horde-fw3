function checkSlug()
{
    slug = document.gallery.gallery_slug.value;
    // Empty slugs are always allowed.
    if (!slug.length) {
        return true;
    }


    var url = slugs['url'];
    var params = new Object();
    params.requestType = 'GallerySlugCheck/slug=' + slug;
    if (slug != slugs.slugText) {
        new Ajax.Request(url, {
            method: 'post',
            parameters: params,
            onComplete: function(transport) {
                var slugFlag = $('slug_flag');
                if (transport.responseText == 1) {
                    if (slugFlag.hasClassName('problem')) {
                        slugFlag.removeClassName('problem');
                    }
                    slugFlag.addClassName('success');
                    $('gallery_submit').enable();
                    slugText = slug;
                } else {
                    if (slugFlag.hasClassName('success')) {
                        slugFlag.removeClassName('success');
                    }
                    slugFlag.addClassName('problem');
                    $('gallery_submit').disable();
                }
            }
        });
    } else {
	    if (slugFlag.hasClassName('problem')) {
	        slugFlag.removeClassName('problem');
	    }
	    slugFlag.addClassName('success');
	    $('gallery_submit').enable();
    }
}
