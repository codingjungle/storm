let template =
    '\
<div id="stormModal{{{id}}}" class="stormModal" tabindex="-1" role="dialog"{{{styleHead}}}>\
<div id="stormModalContent{{{id}}}" class="stormModalContent">\
<div class="stormModalInfo clearfix shadow-lg p-3 rounded{{classes}} {{sizeClass}}"{{{style}}}>\
<div class="stormModalHeader stormColumns clearfix">\
  {{#title}}\
  <h5 class="stormModalTitle stormColumnFluid">{{{title}}}</h5>\
  {{/title}}\
  {{#closeable}}\
  <div id="stormModalClose{{{id}}}" class="stormCloseBox">\
    X\
  </div>\
  {{/closeable}}\
</div>\
<div class="stormModalBody">\
{{{body}}}\
</div>\
</div>\
</div>\
</div>\
';
ips.templates.set('storm.modal.box', template);