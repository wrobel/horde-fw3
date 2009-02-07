function showFace(id)
{
    $('facediv' + id).style.border = '1px solid red';
    $('facethumb' + id).style.border = '1px solid red';
    $('facedivname' + id).style.display = 'block';
}
function hideFace(id)
{
    $('facediv' + id).style.border = 'none';
    $('facethumb' + id).style.border = '1px solid black';
    $('facedivname' + id).style.display = 'none';
}
Event.observe($('photodiv'), 'load', function() {
        $('faces-on-image').immediateDescendants().collect(function(element) {
            element.clonePosition($('photodiv'), {setWidth: false, setHeight: false});
        });
});
