<!DOCTYPE html>

<head>
    <title> <?php echo html_escape($this->common->siteTitle).((isset($title) && !empty($title)) ? ' | '.$title:'');?></title>
    <meta charset="utf-8" />
   <link type="text/css" rel="stylesheet" href="https://source.zoom.us/2.9.7/css/bootstrap.css" />
    <link type="text/css" rel="stylesheet" href="https://source.zoom.us/2.9.7/css/react-select.css" />
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta http-equiv="origin-trial" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo html_escape($this->common->siteFavicon); ?>" />
    <script>
        var baseurl = "<?=base_url();?>";
    </script>
</head>
<body>
        <div class="container">
            <!--<div id="navbar" class="websdktest">-->
                <form class="navbar-form navbar-right" id="meeting_form">
                    <div class="form-group">
                        <input type="hidden" name="display_name" id="display_name" value="<?php echo $display_name;?>" maxLength="100"
                            placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_name'));?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="meeting_number" id="meeting_number" value="<?php echo $meeting_number;?>" maxLength="11"
                            style="width:150px" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_meeting_number'));?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="meeting_pwd" id="meeting_pwd" value="<?php echo $password;?>" style="width:150px"
                            maxLength="32" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_meeting_password'));?>" class="form-control" >
                    </div>
                </form>
            <!--</div>-->
           
        </div>
    

    <input type="hidden" value="<?php echo base_url();?>admin/end_metting/<?php echo $inser_id; ?>" id="leaveurl" >
    <input type="hidden" value="" id="signature" >
    <input type="hidden" value="<?php echo $sdk_key;?>" id="SDK_key" >
    <input type="hidden" value="<?php echo $sdk_secret;?>" id="SDK_secret" >
    <div id="show-test-tool">
    </div>

    <script src="https://source.zoom.us/2.9.7/lib/vendor/react.min.js"></script>
    <script src="https://source.zoom.us/2.9.7/lib/vendor/react-dom.min.js"></script>
    <script src="https://source.zoom.us/2.9.7/lib/vendor/redux.min.js"></script>
    <script src="https://source.zoom.us/2.9.7/lib/vendor/redux-thunk.min.js"></script>
    <script src="https://source.zoom.us/2.9.7/lib/vendor/lodash.min.js"></script>
    <script src="https://source.zoom.us/zoom-meeting-2.9.7.min.js"></script>
    
    <script src="<?=base_url();?>assets/js/zoom/index.js"></script>
    <script src="<?=base_url();?>assets/js/zoom/tool.js"></script>
    <script src="<?=base_url();?>assets/js/zoom/vconsole.min.js"></script>
    <script src="<?=base_url();?>assets/js/zoom/meeting.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script>
        var signature = ZoomMtg.generateSDKSignature({
          meetingNumber: <?php echo $meeting_number;?>,
          sdkKey: '<?php echo $sdk_key;?>',
          sdkSecret: '<?php echo $sdk_secret;?>',
          role:1,
          success: function (res) {
            console.log(res.result);
             signature = res.result;
            //  formData.sdkKey = SDK_key;
             return signature;
          },
        });
     
     
       document.getElementById("signature").value = signature;
        console.log("ravi"+signature);
    </script>
    
</body>

</html>
