
<html>
    <head>
         <title> <?php echo html_escape($this->common->siteTitle).((isset($title) && !empty($title)) ? ' | '.$title:'');?></title>
        <meta charset="utf-8" />
        <meta name="format-detection" content="telephone=no">
        <meta http-equiv="origin-trial" content="">
        <link rel="shortcut icon" type="image/x-icon" href="<?php echo html_escape($this->common->siteFavicon); ?>" />
        <style>
            body {
                margin: 0;
                padding: 0;
            }
            .container {
           
            }     position: relative;;
                width: 640px;
                height:520px;
                border:1px red  solid;
            }
            
            .toolbox {
                position: absolute;
                bottom: 0px;
                border:1px red  solid;
                width: 100%;
                height:50px;
            }
             .toolbox button.callIconVideo {
                position: absolute;
                bottom: 18px;
                left: 320px;
                width: 56px;
                margin: 0 auto;
                background-color: #141414;
                border: none;
                height: 68px;
                border-radius: 7px;
                cursor: pointer;
            }
            .toolbox button.callIconVideo svg {
                    fill: #ffffff;
            }
            button#btnHangup:hover {
                background-color: red;
                transition: 0.3s;
            }
        </style>
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src='https://meet.jit.si/external_api.js'></script>
        <script> var baseurl = "<?php echo base_url();?>"; var liveClassId = "0";</script>
        <script src="<?php echo base_url();?>assets/js/jitsi.js"></script>
        <script>
        var name = "<?php echo $display_name;?>";
        var meetingID = "<?php echo $meeting_number;?>";
       
            $(function(){
                const urlParams = new URLSearchParams(window.location.search);
            
                // var meeting_id = urlParams.get('mid');
                var meeting_id = meetingID;
                
                if (!meeting_id) {
                    alert('meeting id is missing');
                    return;
                }
                // var dispNme = window.prompt('Enter your nick!');
                var dispNme = name;
                if (!dispNme) {
                    dispNme = "New User";
                }
                
                $('#dispName').text(dispNme);
                $('#joinMsg').text('Joining...');
                BindEvent();
                StartMeeting(meeting_id,dispNme);
                
            });
          
        </script>
    </head>
    <body>
       
        <!--<h1 id='dispName'></h1>-->
        <!--<div id='joinMsg'></div>-->
        <div id='container' class="container" style="display:none;">
            <div id="toolbox" class="toolbox" style="display:none;">
                <button id='btnHangup' class="callIconVideo"><svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.535 14.214c.207.832.388 1.233.899 1.598.458.326 2.902.7 3.868.688.509-.007.952-.138 1.304-.462l.017-.016c.858-.851 1.034-3.173.753-4.411-.168-1.205-1.006-2.135-2.395-2.755l-.213-.09c-3.724-1.703-11.8-1.675-15.55.007-1.484.588-2.395 1.562-2.598 2.89-.27 1.067-.112 3.47.758 4.352.374.346.818.477 1.327.484.965.012 3.41-.362 3.867-.687.47-.334.66-.699.848-1.399l.067-.263c.126-.506.203-.652.394-.75 2.08-.95 4.164-.95 6.269.011.15.078.227.204.333.599l.052.204Zm-8.502-.43c.061-.247.147-.57.298-.858a1.97 1.97 0 0 1 .869-.862l.028-.014.03-.014c2.478-1.132 5.017-1.13 7.515.01l.031.015.03.016c.338.173.61.432.804.775.151.267.236.554.294.768l.002.01.057.223c.099.396.16.549.2.623a.206.206 0 0 0 .047.062c.053.018.131.042.237.07.252.066.583.134.951.196.76.127 1.508.2 1.857.196a.825.825 0 0 0 .248-.033.166.166 0 0 0 .044-.02.585.585 0 0 0 .044-.067c.035-.059.076-.143.118-.256.085-.229.157-.526.204-.868.097-.703.065-1.407-.027-1.813l-.015-.062-.008-.063c-.075-.532-.434-1.105-1.51-1.587l-.219-.093-.017-.008c-1.573-.72-4.269-1.134-7.14-1.13-2.868.004-5.58.427-7.173 1.141l-.03.014-.03.012c-1.137.45-1.568 1.06-1.67 1.721l-.01.072-.018.07c-.073.29-.114.953-.016 1.677.047.345.119.652.207.894.043.119.085.209.122.273.017.03.032.052.042.066.018.013.035.02.054.027.037.013.109.03.24.032.349.004 1.098-.069 1.857-.196.368-.062.7-.13.952-.196.105-.028.184-.051.237-.07a.174.174 0 0 0 .04-.05c.027-.044.08-.155.16-.454l.064-.25Z"></path></svg></button>
            </div>
            <div id='jitsi-meet-conf-container'></div>
        </div>
    </body>
</html>
