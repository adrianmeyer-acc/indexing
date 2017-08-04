//*******************************************************************
// Ajax get call
//*******************************************************************
function ajaxGet(aURL, aLoadingDone, aCustomData) {
  var xmlHttpReq = false;
  var self = this;
  // Mozilla/Safari
  if (window.XMLHttpRequest) {
      self.xmlHttpReq = new XMLHttpRequest();
  }
  // IE
  else if (window.ActiveXObject) {
    self.xmlHttpReq = new ActiveXObject("Microsoft.XMLHTTP");
  }
  self.xmlHttpReq.onreadystatechange = function() {
      if (self.xmlHttpReq.readyState == 4) {
          eval( 'var data=' + self.xmlHttpReq.responseText + ';' );
          aLoadingDone( data, aCustomData);
      }
  }
  self.xmlHttpReq.open('GET', aURL, true);
  self.xmlHttpReq.send(null);
}
 
 
 
//*******************************************************************
// Ajax post call
//*******************************************************************
function ajaxPost( aURL, aLoadingDone, aPostData, aCustomData ) {
  var xmlHttpReq = false;
  var self = this;
  // Mozilla/Safari
  if (window.XMLHttpRequest) {
      self.xmlHttpReq = new XMLHttpRequest();
  }
  // IE
  else if (window.ActiveXObject) {
    self.xmlHttpReq = new ActiveXObject("Microsoft.XMLHTTP");
  }
  self.xmlHttpReq.onreadystatechange = function() {
      if (self.xmlHttpReq.readyState == 4) {
          eval( 'var data=' + self.xmlHttpReq.responseText + ';' );
          aLoadingDone( data, aCustomData);
      }
  }
  
  // assemble post data from passed aPostData object
  var postData = '';
  for (var field in aPostData ) {
    if (postData !='') {
      postData += '&';
    }
    postData += (''+field) + '=' + encodeURIComponent( aPostData[field] );
  }
  
  self.xmlHttpReq.open('POST', aURL, true);
  self.xmlHttpReq.setRequestHeader("Content-type","application/x-www-form-urlencoded");
  self.xmlHttpReq.send( postData );
}