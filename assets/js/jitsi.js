var apiObj = null;

function BindEvent(){
    
    $("#btnHangup").on('click', function () {
        apiObj.executeCommand('hangup');
        window.location.href = baseurl+"end_metting/"+liveClassId;
    });
    $("#TeacherbtnHangup").on('click', function () {
        apiObj.executeCommand('hangup');
        window.location.href = baseurl+"teacher-end-metting/"+liveClassId;
    });
    $("#btnCustomMic").on('click', function () {
        apiObj.executeCommand('toggleAudio');
    });
    $("#btnCustomCamera").on('click', function () {
        apiObj.executeCommand('toggleVideo');
    });
    $("#btnCustomTileView").on('click', function () {
        apiObj.executeCommand('toggleTileView');
    });
    $("#btnScreenShareCustom").on('click', function () {
        apiObj.executeCommand('toggleShareScreen');
    });
    //  new featurs add 
    $("#stopShareVideo").on('click', function () {
        apiObj.executeCommand('stopShareVideo');
    });
    $("#toggleChat").on('click', function () {
        apiObj.executeCommand('toggleChat');
    });
    $("#toggleRaiseHand").on('click', function () {
        apiObj.executeCommand('toggleRaiseHand');
    });
    $("#endConference").on('click', function () {
        apiObj.executeCommand('endConference');
    });
    $("#muteEveryone").on('click', function () {
        apiObj.executeCommand('muteEveryone', 'audio');
    });
    $("#muteEveryoneVideo").on('click', function () {
        apiObj.executeCommand('muteEveryone', 'video');
    });
    $("#toggleCameraMirror").on('click', function () {
        apiObj.executeCommand('toggleCameraMirror');
    });
    $("#toggleVirtualBackgroundDialog").on('click', function () {
        apiObj.executeCommand('toggleVirtualBackgroundDialog');
    });
  
    $("#sendChatMessage").on('click', function () {
        apiObj.executeCommand('sendChatMessage',{
            message: 'Hello Guys ', //the text message
            to: string, // the receiving participant ID or empty string/undefined for group chat.
            ignorePrivacy: true // true if the privacy notification should be ignored. Defaulted to false.
        });
    });
    $("#toggleParticipantsPane").on('click', function () {
        apiObj.getParticipantsInfo('toggleParticipantsPane');
        // apiObj.getRoomsInfo('toggleParticipantsPane');
        var data = Object.entries(apiObj);
        var result = Object.entries(data[13]);
        var Pre_SD = Object.entries(result[1][1]);
        // using forEach
        Pre_SD.forEach( function myFunction(item) {
            var SD_id = item[0];
            var SD_info = item[1];
          
            
            console.log(SD_id);
            console.log(SD_info.displayName);
        });
    });
  
    
    
    $("#stopRecording").on('click', function () {
        apiObj.executeCommand('stopRecording',{mode: local});
    });
    $("#startRecording").on('click', function () {
        apiObj.executeCommand('startRecording', {
            mode: local, //recording mode, either `local`, `file` or `stream`.
            // dropboxToken: string, //dropbox oauth2 token.
            onlySelf: boolean,  //Whether to only record the local streams. Only applies to `local` recording mode.
            shouldShare: boolean, //whether the recording should be shared with the participants or not. Only applies to certain jitsi meet deploys.
            rtmpStreamKey: string, //the RTMP stream key.
            rtmpBroadcastID: string, //the RTMP broadcast ID.
            youtubeStreamKey: string, //the youtube stream key.
            youtubeBroadcastID: string //the youtube broacast ID.
        });
    });
}

function StartMeeting(roomName,dispNme){
    const domain = 'meet.jit.si';

    //var roomName = 'newRoome_' + (new Date()).getTime();
    
    const options = {
        roomName: roomName,
        width: '100%',
        height: '100%',
        parentNode: document.querySelector('#jitsi-meet-conf-container'),
        DEFAULT_REMOTE_DISPLAY_NAME: 'New User',
        userInfo: {
            displayName: dispNme
        },
        configOverwrite:{
            doNotStoreRoom: true,
            startVideoMuted: 0,
            startWithVideoMuted: true,
            startWithAudioMuted: true,
            enableWelcomePage: false,
            prejoinPageEnabled: false,
            disableRemoteMute: true,
            remoteVideoMenu: {
                disableKick: true
            },
        },
        interfaceConfigOverwrite: {
            filmStripOnly: false,
            SHOW_JITSI_WATERMARK: false,
            SHOW_WATERMARK_FOR_GUESTS: false,
            DEFAULT_REMOTE_DISPLAY_NAME: 'New User',
            TOOLBAR_BUTTONS: [ 'camera',
               'chat',
               'closedcaptions',
               'desktop',
               'download',
               'embedmeeting',
               'etherpad',
               'feedback',
               'filmstrip',
               'fullscreen',
            //   'hangup',
               'help',
               'highlight',
               'invite',
               'linktosalesforce',
               'livestreaming',
               'microphone',
               'noisesuppression',
               'participants-pane',
               'profile',
               'raisehand',
               'recording',
               'security',
               'select-background',
               'settings',
               'shareaudio',
               'sharedvideo',
               'shortcuts',
               'stats',
               'tileview',
               'toggle-camera',
               'videoquality',
               'whiteboard',
               ]
        },
        onload: function () {
            // alert('loaded');
            $('#joinMsg').show();
            $('#container').show();
            $('#toolbox').show();
        }
    };
    apiObj = new JitsiMeetExternalAPI(domain, options);

    apiObj.addEventListeners({
        readyToClose: function () {
            //alert('going to close');
            $('#jitsi-meet-conf-container').empty();
            $('#toolbox').show();
            $('#container').hide();
            $('#joinMsg').show().text('Meeting Ended');
        },
        audioMuteStatusChanged: function (data) {
            if(data.muted)
                $("#btnCustomMic").text('Unmute');
            else
                $("#btnCustomMic").text('Mute');
        },
        videoMuteStatusChanged: function (data) {
            if(data.muted)
                $("#btnCustomCamera").text('Start Cam');
            else
                $("#btnCustomCamera").text('Stop Cam');
        },
        tileViewChanged: function (data) {
            
        },
        screenSharingStatusChanged: function (data) {
            if(data.on)
                $("#btnScreenShareCustom").text('Stop SS');
            else
                $("#btnScreenShareCustom").text('Start SS');
        },
        participantJoined: function(data){
            console.log('participantJoined', data);
        },
        participantLeft: function(data){
            console.log('participantLeft', data);
        }
    });

    apiObj.executeCommand('subject', 'Jetsi Meet Class Room ');
}

