(function(){
    // ZoomMtg.preLoadWasm();
    // ZoomMtg.prepareJssdk();
    
    /*testTool = window.testTool;
    window.addEventListener('load', function(e){
        e.preventDefault();
        
       var API_KEY = document.getElementById('api_key').value;
        var meetConfig = {
            apiKey: API_KEY,
            meetingNumber: parseInt(document.getElementById('meeting_number').value),
            userName: document.getElementById('display_name').value,
            passWord: document.getElementById('meeting_pwd').value,
            leaveUrl: document.getElementById('leaveurl').value,
          
        };
        ZoomMtg.init({
            leaveUrl: meetConfig.leaveUrl,
            success: function () {
                ZoomMtg.join(
                    {
                        meetingNumber: meetConfig.meetingNumber,
                        userName: meetConfig.userName,
                        signature: document.getElementById('signature').value,
                        apiKey: meetConfig.apiKey,
                        passWord: meetConfig.passWord,
                        success: function(res){
                            $('#nav-tool').hide();
                           
                        },
                        error: function(res) {
                            console.log(res);
                        }
                    }
                );
            },
            error: function(res) {
                console.log(res);
            }
        });

    });*/

  //   function beginJoin() {
  //       var API_KEY = document.getElementById('api_key').value;
  //       var meetingNumber= parseInt(document.getElementById('meeting_number').value);
  //       var userName= document.getElementById('display_name').value;
  //       var passWord= document.getElementById('meeting_pwd').value;
  //       var leaveUrl= document.getElementById('leaveurl').value;
  //       var signature= document.getElementById('signature').value;
  //   ZoomMtg.init({
  //     leaveUrl: leaveUrl,
  //     webEndpoint: undefined,
  //     success: function () {
  //       $.i18n.reload("en-US");
  //       ZoomMtg.join({
  //         meetingNumber: meetingNumber,
  //         userName: userName,
  //         signature:signature,
  //         apiKey: API_KEY,
  //         userEmail:"",
  //         passWord: passWord,
  //         success: function (res) {
  //           console.log("join meeting success");
  //           console.log("get attendeelist");
  //           ZoomMtg.getAttendeeslist({});
  //           ZoomMtg.getCurrentUser({
  //             success: function (res) {
  //               console.log("success getCurrentUser", res.result.currentUser);
  //             },
  //           });
  //         },
  //         error: function (res) {
  //           console.log(res);
  //         },
  //       });
  //     },
  //     error: function (res) {
  //       console.log(res);
  //     },
  //   });
  // }
//   function beginJoin() {
//      var API_KEY = document.getElementById('api_key').value;
//         var meetingNumber= parseInt(document.getElementById('meeting_number').value);
//         var userName= document.getElementById('display_name').value;
//         var passWord= document.getElementById('meeting_pwd').value;
//         var leaveUrl= document.getElementById('leaveurl').value;
//         var signature= document.getElementById('signature').value;
//     ZoomMtg.init({
//       leaveUrl:leaveUrl,
//       success: function () {
        
//         ZoomMtg.i18n.load("en-US");
//         ZoomMtg.i18n.reload("en-US");
//         ZoomMtg.join({
//           meetingNumber: meetingNumber,
//           userName:userName,
//           signature: signature,
//           apiKey: API_KEY,
          
//           passWord: passWord,
//           success: function (res) {
//             // console.log("join meeting success");
//             // console.log("get attendeelist");
//             ZoomMtg.getAttendeeslist({});
//             ZoomMtg.getCurrentUser({
//               success: function (res) {
//                 console.log("success getCurrentUser", res.result.currentUser);
//               },
//             });
//           },
//           error: function (res) {
//             // console.log(res);
//           },
//         });
//       },
//       error: function (res) {
//         // console.log(res);
//       },
//     });

//     ZoomMtg.inMeetingServiceListener('onUserJoin', function (data) {
//       // console.log('inMeetingServiceListener onUserJoin', data);
//     });
  
//     ZoomMtg.inMeetingServiceListener('onUserLeave', function (data) {
//       // console.log('inMeetingServiceListener onUserLeave', data);
//     });
  
//     ZoomMtg.inMeetingServiceListener('onUserIsInWaitingRoom', function (data) {
//       // console.log('inMeetingServiceListener onUserIsInWaitingRoom', data);
//     });
  
//     ZoomMtg.inMeetingServiceListener('onMeetingStatus', function (data) {
//       // console.log('inMeetingServiceListener onMeetingStatus', data);
//     });
//   }
// beginJoin();



window.addEventListener('DOMContentLoaded', function(event) {
  console.log('DOM fully loaded and parsed');
  websdkready();
});

function websdkready() {
  var testTool = window.testTool;
  if (testTool.isMobileDevice()) {
    vConsole = new VConsole();
  }
  console.log("checkSystemRequirements");
  console.log(JSON.stringify(ZoomMtg.checkSystemRequirements()));

  // it's option if you want to change the WebSDK dependency link resources. setZoomJSLib must be run at first
  // if (!china) ZoomMtg.setZoomJSLib('https://source.zoom.us/2.9.7/lib', '/av'); // CDN version default
  // else ZoomMtg.setZoomJSLib('https://jssdk.zoomus.cn/2.9.7/lib', '/av'); // china cdn option
  // ZoomMtg.setZoomJSLib('http://localhost:9999/node_modules/@zoomus/websdk/dist/lib', '/av'); // Local version default, Angular Project change to use cdn version
  ZoomMtg.preLoadWasm(); // pre download wasm file to save time.

  var SDK_KEY = "xwYMnt52tXkxaxr9lxa1hNI4IxnNqHi26iFw";
  /**
   * NEVER PUT YOUR ACTUAL SDK SECRET IN CLIENT SIDE CODE, THIS IS JUST FOR QUICK PROTOTYPING
   * The below generateSignature should be done server side as not to expose your SDK SECRET in public
   * You can find an eaxmple in here: https://marketplace.zoom.us/docs/sdk/native-sdks/web/essential/signature
   */
  var SDK_SECRET = "z8dZ3DgN9QeGXENLECR32NIW3HwhG4K4Kqce";

  // some help code, remember mn, pwd, lang to cookie, and autofill.
  document.getElementById("display_name").value =
    "CDN" +
    ZoomMtg.getJSSDKVersion()[0] +
    testTool.detectOS() +
    "#" +
    testTool.getBrowserInfo();
  document.getElementById("meeting_number").value = testTool.getCookie(
    "meeting_number"
  );
  document.getElementById("meeting_pwd").value = testTool.getCookie(
    "meeting_pwd"
  );
  if (testTool.getCookie("meeting_lang"))
    document.getElementById("meeting_lang").value = testTool.getCookie(
      "meeting_lang"
    );

  document
    .getElementById("meeting_lang")
    .addEventListener("change", function (e) {
      testTool.setCookie(
        "meeting_lang",
        document.getElementById("meeting_lang").value
      );
      testTool.setCookie(
        "_zm_lang",
        document.getElementById("meeting_lang").value
      );
    });
  // copy zoom invite link to mn, autofill mn and pwd.
  document
    .getElementById("meeting_number")
    .addEventListener("input", function (e) {
      var tmpMn = e.target.value.replace(/([^0-9])+/i, "");
      if (tmpMn.match(/([0-9]{9,11})/)) {
        tmpMn = tmpMn.match(/([0-9]{9,11})/)[1];
      }
      var tmpPwd = e.target.value.match(/pwd=([\d,\w]+)/);
      if (tmpPwd) {
        document.getElementById("meeting_pwd").value = tmpPwd[1];
        testTool.setCookie("meeting_pwd", tmpPwd[1]);
      }
      document.getElementById("meeting_number").value = tmpMn;
      testTool.setCookie(
        "meeting_number",
        document.getElementById("meeting_number").value
      );
    });

  document.getElementById("clear_all").addEventListener("click", function (e) {
    testTool.deleteAllCookies();
    document.getElementById("display_name").value = "";
    document.getElementById("meeting_number").value = "";
    document.getElementById("meeting_pwd").value = "";
    document.getElementById("meeting_lang").value = "en-US";
    document.getElementById("meeting_role").value = 0;
    window.location.href = "/index.html";
  });

  // click join meeting button
  document
    .getElementById("join_meeting")
    .addEventListener("click", function (e) {
      e.preventDefault();
      var meetingConfig = testTool.getMeetingConfig();
      if (!meetingConfig.mn || !meetingConfig.name) {
        alert("Meeting number or username is empty");
        return false;
      }
 
      testTool.setCookie("meeting_number", meetingConfig.mn);
      testTool.setCookie("meeting_pwd", meetingConfig.pwd);

      var signature = ZoomMtg.generateSDKSignature({
        meetingNumber: meetingConfig.mn,
        sdkKey: SDK_KEY,
        sdkSecret: SDK_SECRET,
        role: meetingConfig.role,
        success: function (res) {
          console.log(res.result);
          meetingConfig.signature = res.result;
          meetingConfig.sdkKey = SDK_KEY;
          var joinUrl = "https://kamleshyadav.in/Eacademy_update/admin/live-class?" + testTool.serialize(meetingConfig);
          console.log(joinUrl);
          window.open(joinUrl, "_blank");
        },
      });
    });

  function copyToClipboard(elementId) {
    var aux = document.createElement("input");
    aux.setAttribute("value", document.getElementById(elementId).getAttribute('link'));
    document.body.appendChild(aux);  
    aux.select();
    document.execCommand("copy");
    document.body.removeChild(aux);
  }
    
  // click copy jon link button
  window.copyJoinLink = function (element) {
    var meetingConfig = testTool.getMeetingConfig();
    if (!meetingConfig.mn || !meetingConfig.name) {
      alert("Meeting number or username is empty");
      return false;
    }
    var signature = ZoomMtg.generateSDKSignature({
      meetingNumber: meetingConfig.mn,
      sdkKey: SDK_KEY,
      sdkSecret: SDK_SECRET,
      role: meetingConfig.role,
      success: function (res) {
        console.log(res.result);
        meetingConfig.signature = res.result;
        meetingConfig.sdkKey = SDK_KEY;
        var joinUrl =
          testTool.getCurrentDomain() +
          "http://localhost/zMeeting/CDN/meeting.html?"+testTool.serialize(meetingConfig);
        document.getElementById('copy_link_value').setAttribute('link', joinUrl);
        copyToClipboard('copy_link_value');
        
      },
    });
  };

}

})();
