pulled. now
compare current branch and main
if we should merge it with main
as we want to move forward with new feature

---

the project is broken.
even the design is broken. I dont like the previous design

now make a lan video and chat app with laravel 12 and latest php, if nothing is reusable, build from scratch
with modern professional production and lite ui and excellent ux

---

docker compose up --build -d

---

> [internal] load build context:

---

failed to solve: invalid file request node_modules/.bin/clean-orphaned-assets

---

admin@admin.com / 123456 can not login, if encryption has problem, is it saving password encrypted or not

---

can not login, no error has shown

---

/login

showed error : blocked : invalid origin

when trying to access from another device via ip

allow it

---

why trying to camera on from another device on lan - cannot read properties of undefined (reading 'getUserMedia')

---

I want lan chat with camera and mic access between 2 devices and website will be accessed with ip, do necessary changes

---

https://<host-lan-ip>:<port> can not access from another pc
:
ERR_SSL_PROTOCOL_ERROR here means the other PC is reaching 192.168.0.114:8000, but that port is serving plain HTTP, not HTTPS.

So right now:

http://192.168.0.114:8000 can work
https://192.168.0.114:8000 will fail exactly like your screenshot
Why:

your current Docker/Nginx config only listens on port 80 inside the container and maps it to host port 8000
there is no TLS certificate or HTTPS listener configured on that port
What to do now:

Test from the other PC with http://192.168.0.114:8000
If that does not open, allow port 8000 in Windows Firewall on the host PC
If you need camera/mic from another PC, plain HTTP by LAN IP is the blocker; you need real HTTPS setup
Important constraint:

browser camera/mic on http://192.168.x.x is usually blocked
for cross-device LAN video/audio, you need https://192.168.x.x or a trusted local domain over HTTPS
So the current state is:

LAN chat over HTTP: possible
LAN camera/mic over IP: not reliable without HTTPS
If you want, I can make the project serve proper local HTTPS next by adding one of these:

self-signed HTTPS in Nginx/Docker
Caddy with local TLS
mkcert-based trusted LAN certificate
The cleanest option for Windows + LAN is mkcert + Nginx/Caddy.


---


in new pc : docker compose up --build -d
failed to solve: process "/bin/sh -c apt-get update && apt-get install -y     nginx     git     unzip     curl     libpng-dev     libonig-dev     libxml2-dev     zip     libzip-dev     libssl-dev     redis-server     supervisor     dos2unix     && docker-php-ext-configure intl     && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip sockets intl" did not complete successfully: exit code: 1


---

allow https on Lan http://192.168.0.104:8080/ and localhost:8080 

---

altough another user is logged in https://192.168.50.104:8080 still shows offline

---

even trying from same pc, still shows offline

---

now even if one pc don't have mic and camera, still it will receive video and audio feed

---

no ringing mechanism when called

---
when try to receive call-

Failed to execute 'setRemoteDescription' on 'RTCPeerConnection': Failed to parse SessionDescription. a=fmtp:49 repair-window=10000000 Invalid SDP line.

---


https://192.168.50.104:8080/api/call-state?peer_id=1
Request Method:
GET
Status Code:
429 Too Many Requests

---

when try to receive call-

Failed to execute 'setRemoteDescription' on 'RTCPeerConnection': Failed to parse SessionDescription. a=rtcp-fb:49 transport-cc Invalid SDP line.

---

when tried to receive call - Failed to execute 'setRemoteDescription' on 'RTCPeerConnection': Failed to parse SessionDescription. a=ssrc:1195093803 label:9fd31aa3-8044-49ff-b6df-434b47e19195 Invalid SDP line.

---

when try to receive call-
Failed to execute 'setRemoteDescription' on 'RTCPeerConnection': Failed to parse SessionDescription. a=rtpmap:127 ulpfec/90000 Invalid SDP line.

---

antigravity : Failed to execute 'setRemoteDescription' on 'RTCPeerConnection': Failed to parse SessionDescription. audio 9 UDP/TLS/RTP/SAVPF 111 103 9 0 8 105 13 110 113 126 Expects m line.

--- 

Stich :  app name is VideoChat , update all ui. its a workspace or business app like google meet just focussed to integrate with other app to conduct team collaboration within Lan

---

Stich : give a available online page where I can find online users

---

Stich : public home page is not that conversion friendly for signup, comeup with something modern clean and exciting

