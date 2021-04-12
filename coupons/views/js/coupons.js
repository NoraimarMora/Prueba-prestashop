(function(){

    'use strict';
  
    var canvas = document.getElementById('scratch'),
        context = canvas.getContext('2d');
  
    // default value
    context.globalCompositeOperation = 'source-over';
  
    //----------------------------------------------------------------------------
  
    var x, y, radius;
  
    x = y = radius = 150 / 2;
  
    // fill circle
    context.beginPath();
    context.fillStyle = '#C0C0C0';
    context.rect(0, 0, 300, 60);
    context.fill();
  
    //----------------------------------------------------------------------------
  
    var isDrag = false;
  
    function clearArc(x, y) {
      context.globalCompositeOperation = 'destination-out';
      context.beginPath();
      context.arc(x, y, 10, 0, Math.PI * 2, false);
      context.fill();
    }
  
    canvas.addEventListener('mousedown', function(event) {
      isDrag = true;
  
      clearArc(event.offsetX, event.offsetY);
      judgeVisible();
    }, false);
  
    canvas.addEventListener('mousemove', function(event) {
      if (!isDrag) {
        return;
      }
  
      clearArc(event.offsetX, event.offsetY);
      judgeVisible();
    }, false);
  
    canvas.addEventListener('mouseup', function(event) {
      isDrag = false;
    }, false);
  
    canvas.addEventListener('mouseleave', function(event) {
      isDrag = false;
    }, false);
  
    //----------------------------------------------------------------------------
  
    canvas.addEventListener('touchstart', function(event) {
      if (event.targetTouches.length !== 1) {
        return;
      }
  
      event.preventDefault();
  
      isDrag = true;
  
      clearArc(event.touches[0].offsetX, event.touches[0].offsetY);
      judgeVisible();
    }, false);
  
    canvas.addEventListener('touchmove', function(event) {
      if (!isDrag || event.targetTouches.length !== 1) {
        return;
      }
  
      event.preventDefault();
  
      clearArc(event.touches[0].offsetX, event.touches[0].offsetY);
      judgeVisible();
    }, false);
  
    canvas.addEventListener('touchend', function(event) {
      isDrag = false;
    }, false);

    var canvas2 = document.getElementById('scratch2'),
        context2 = canvas2.getContext('2d');
  
    // default value
    context2.globalCompositeOperation = 'source-over';
  
    //----------------------------------------------------------------------------
  
    var x2, y2, radius2;
  
    x2 = y2 = radius2 = 150 / 2;
  
    // fill circle
    context2.beginPath();
    context2.fillStyle = '#C0C0C0';
    context2.rect(0, 0, 300, 60);
    context2.fill();
  
    //----------------------------------------------------------------------------
  
    var isDrag2 = false;
  
    function clearArc2(x2, y2) {
      context2.globalCompositeOperation = 'destination-out';
      context2.beginPath();
      context2.arc(x2, y2, 10, 0, Math.PI * 2, false);
      context2.fill();
    }
  
    canvas2.addEventListener('mousedown', function(event) {
      isDrag2 = true;
  
      clearArc2(event.offsetX, event.offsetY);
      judgeVisible2();
    }, false);
  
    canvas2.addEventListener('mousemove', function(event) {
      if (!isDrag2) {
        return;
      }
  
      clearArc2(event.offsetX, event.offsetY);
      judgeVisible2();
    }, false);
  
    canvas2.addEventListener('mouseup', function(event) {
      isDrag2 = false;
    }, false);
  
    canvas2.addEventListener('mouseleave', function(event) {
      isDrag2 = false;
    }, false);
  
    //----------------------------------------------------------------------------
  
    canvas2.addEventListener('touchstart', function(event) {
      if (event.targetTouches.length !== 1) {
        return;
      }
  
      event.preventDefault();
  
      isDrag2 = true;
  
      clearArc2(event.touches[0].offsetX, event.touches[0].offsetY);
      judgeVisible2();
    }, false);
  
    canvas2.addEventListener('touchmove', function(event) {
      if (!isDrag2 || event.targetTouches.length !== 1) {
        return;
      }
  
      event.preventDefault();
  
      clearArc2(event.touches[0].offsetX, event.touches[0].offsetY);
      judgeVisible2();
    }, false);
  
    canvas2.addEventListener('touchend', function(event) {
      isDrag2 = false;
    }, false);
  
    //----------------------------------------------------------------------------
  
    function judgeVisible() {
      var imageData = context.getImageData(0, 0, 150, 150),
          pixels = imageData.data,
          result = {},
          i, len;
  
      // count alpha values
      for (i = 3, len = pixels.length; i < len; i += 4) {
        result[pixels[i]] || (result[pixels[i]] = 0);
        result[pixels[i]]++;
      }
  
      /*document.getElementById('gray-count').innerHTML = result[255];
      document.getElementById('erase-count').innerHTML = result[0];*/
    }
  
    function judgeVisible2() {
        var imageData = context2.getImageData(0, 0, 150, 150),
            pixels = imageData.data,
            result = {},
            i, len;
    
        // count alpha values
        for (i = 3, len = pixels.length; i < len; i += 4) {
          result[pixels[i]] || (result[pixels[i]] = 0);
          result[pixels[i]]++;
        }
    
        /*document.getElementById('gray-count').innerHTML = result[255];
        document.getElementById('erase-count').innerHTML = result[0];*/
      }
    
      document.addEventListener('DOMContentLoaded', judgeVisible2, false);
      document.addEventListener('DOMContentLoaded', judgeVisible, false);
  
  }());