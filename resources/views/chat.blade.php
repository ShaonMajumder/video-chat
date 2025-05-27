@extends('layouts.app')

@section('title', 'Live Chat')

@section('styles')
<style>
    .chat-wrapper {
        flex: 3; /* takes 3 parts of available space */
        display: flex;

        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        height: 75vh;
        max-width: 700px;
        margin: 2rem auto 0;
        overflow: hidden;
    }

    header.chat-header {
        background: #2563eb;
        color: white;
        font-weight: 700;
        font-size: 1.5rem;
        padding: 1rem 1.5rem;
        user-select: none;
    }

    ul#messages {
        flex-grow: 1;
        overflow-y: auto;
        padding: 1.5rem 1.5rem;
        margin: 0;
        list-style: none;
        background: #f3f4f6;
        scroll-behavior: smooth;
    }

    ul#messages li.message {
        max-width: 70%;
        margin-bottom: 1rem;
        display: flex;
        word-break: break-word;
    }

    .bubble {
        padding: 12px 18px;
        border-radius: 20px;
        font-size: 1rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        line-height: 1.3;
    }

    .bubble-left {
        background: #e0e7ff;
        color: #1e40af;
        margin-right: auto;
        border-bottom-left-radius: 0;
    }

    .bubble-right {
        background: #2563eb;
        color: white;
        margin-left: auto;
        border-bottom-right-radius: 0;
    }

    .input-area {
        display: flex;
        padding: 1rem 1.5rem;
        border-top: 1px solid #ddd;
        background: white;
    }

    .input-area input[type="text"] {
        flex-grow: 1;
        padding: 12px 16px;
        font-size: 1rem;
        border-radius: 9999px;
        border: 1px solid #cbd5e1;
        outline-offset: 2px;
        transition: border-color 0.2s ease;
    }

    .input-area input[type="text"]:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
    }

    .input-area button {
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 9999px;
        padding: 0 24px;
        margin-left: 1rem;
        font-weight: 700;
        cursor: pointer;
        font-size: 1rem;
        transition: background-color 0.3s ease;
    }

    .input-area button:hover {
        background: #1e40af;
    }

    @media (max-width: 640px) {
        .chat-wrapper {
            height: 100vh;
            border-radius: 0;
            margin: 0;
        }
    }


    .chat-container {
        display: flex;
        gap: 1rem; /* spacing between left and right */
        height: 80vh; /* or any height you want */
    }


    /* Online users panel on the right */
    .online-users {
        flex: 1; /* takes 1 part of available space */
        border: 1px solid #ccc;
        border-radius: 6px;
        padding: 1rem;
        background: #f9f9f9;
        overflow-y: auto; /* scroll if too many users */
    }



    /* Online user list style */
    #user-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

</style>
@endsection

@section('content')
<div class="chat-container" role="main" aria-label="Live chat interface">
    @if(!empty($receiverId))
    <div class="chat-wrapper" aria-label="Chat messages area">
        <header class="chat-header" id="chat-header">🗨️ Live Video Chat</header>

        <ul id="messages" aria-live="polite" aria-relevant="additions"></ul>

        <div class="input-area">
            <input type="text" id="message" placeholder="Type your message..." autocomplete="off" aria-label="Message input" />
            <button id="sendBtn" aria-label="Send message">Send</button>
        </div>
    </div>
    @endif

    <div class="online-users" aria-label="Online users list">
        <header class="chat-header">🟢 Online Users</header>
        <ul id="user-list" aria-label="Online users list">
            <!-- Populated by JS -->
        </ul>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
        // axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // const pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
    //     cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
    //     wsHost: window.location.hostname,
    //     wsPort: 6001,
    //     forceTLS: false,
    //     disableStats: true,
    //     enabledTransports: ['ws', 'wss'],
    // });

    // const channel = pusher.subscribe('chat');
    // const currentId = Math.random().toString(36).substr(2, 9);
    const urlParams = new URLSearchParams(window.location.search);
    const receiverId = urlParams.get('receiver');

    // channel.bind('App\\Events\\MessageSent', function(data) {
    //     log(data)
    //     const isSelf = data.sender_id === currentId;

    //     const li = document.createElement("li");
    //     li.classList.add('message');

    //     const bubble = document.createElement("div");
    //     bubble.classList.add('bubble', isSelf ? 'bubble-right' : 'bubble-left');
    //     bubble.textContent = data.message;

    //     li.appendChild(bubble);
    //     document.getElementById("messages").appendChild(li);

    //     // Scroll down
    //     const messages = document.getElementById('messages');
    //     messages.scrollTop = messages.scrollHeight;
    // });

    // document.getElementById('sendBtn').addEventListener('click', sendMessage);
    // document.getElementById('message').addEventListener('keyup', function(e) {
    //     if (e.key === 'Enter') sendMessage();
    // });

    // function sendMessage() {
    //     const input = document.getElementById('message');
    //     const message = input.value.trim();
    //     alert(message)
    //     if (!message) return;
    //     axios.post('{{ route('chat.send') }}', { message, receiver_id: receiverId })
    //         .then(() => {
    //             input.value = '';
    //         })
    //         .catch(err => {
    //             alert('Message failed to send. Please try again.');
    //             console.error(err);
    //         });
            
    // }

    async function subscribeToChannel(receiverId) {
        try {
            const response = await fetch(`/api/me?receiver=${receiverId}`, {
                credentials: 'include'
            });
            const user = await response.json();

            const currentUserId = user.user.id;
            if (user.receiver) {
                document.getElementById("chat-header").textContent = `🗨️ ${user.receiver.name}`;
            }

            log('user', user);
            log('channel', `message-box.${currentUserId}`);

            window.Echo.private(`message-box.${currentUserId}`)
                .listen('NewMessage', (data) => {
                    const isSelf = data.sender_id === currentUserId;
                    const li = document.createElement("li");
                    li.classList.add('message');

                    const bubble = document.createElement("div");
                    bubble.classList.add('bubble', isSelf ? 'bubble-right' : 'bubble-left');
                    bubble.textContent = DOMPurify.sanitize(data.message);

                    li.appendChild(bubble);
                    document.getElementById("messages").appendChild(li);

                    const messages = document.getElementById('messages');
                    messages.scrollTop = messages.scrollHeight;

                    log('data', data, 'isSelf', isSelf);
                });

            // Attach send button events
            document.getElementById('sendBtn')?.addEventListener('click', sendMessage);
            document.getElementById('message')?.addEventListener('keyup', function (e) {
                if (e.key === 'Enter') sendMessage();
            });

            function sendMessage() {
                const input = document.getElementById('message');
                const message = input.value.trim();
                if (!message) return;

                axios.post('{{ route('chat.send') }}', {
                    message,
                    receiver_id: receiverId
                }, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                }).then(() => {
                    input.value = '';
                }).catch(err => {
                    alert('Message failed to send. Please try again.');
                    console.error(err);
                });

                const li = document.createElement("li");
                li.classList.add('message');

                const bubble = document.createElement("div");
                bubble.classList.add('bubble', 'bubble-right');
                bubble.textContent = DOMPurify.sanitize(message);

                li.appendChild(bubble);
                document.getElementById("messages").appendChild(li);

                const messages = document.getElementById('messages');
                messages.scrollTop = messages.scrollHeight;
            }

            return user;
        } catch (error) {
            console.error("Error subscribing to channel:", error);
            return null;
        }
    }

    // async function subscribeToChannel(receiverId) {
    //     fetch(`/api/me?receiver=${receiverId}`, {
    //         credentials: 'include' // Send jwt cookie
    //     })
    //     .then(response => response.json())
    //     .then(user => {
    //         const currentUserId = user.user.id;
    //         if(user.receiver){
    //             document.getElementById("chat-header").textContent = `🗨️ ${user.receiver.name}`;
    //         }
            
    //         log('user',user)
    //         log('channel', `message-box.${currentUserId}`)
    //         window.Echo.private(`message-box.${currentUserId}`)
    //             .listen('NewMessage', (data) => {
    //                 const isSelf = data.sender_id === currentUserId;
    //                 const li = document.createElement("li");
    //                 li.classList.add('message');

    //                 const bubble = document.createElement("div");
    //                 bubble.classList.add('bubble', isSelf ? 'bubble-right' : 'bubble-left');
    //                 bubble.textContent = DOMPurify.sanitize(data.message);

    //                 li.appendChild(bubble);
    //                 document.getElementById("messages").appendChild(li);

    //                 // Auto scroll to latest
    //                 const messages = document.getElementById('messages');
    //                 messages.scrollTop = messages.scrollHeight;

    //                 log('data',data,'iseelf', isSelf)
    //             });
    //     });


    //      // Send message handler
    //     document.getElementById('sendBtn')>.addEventListener('click', sendMessage);
    //     document.getElementById('message')?.addEventListener('keyup', function(e) {
    //         if (e.key === 'Enter') sendMessage();
    //     });

    //     function sendMessage() {
    //         const input = document.getElementById('message');
    //         const message = input.value.trim();
    //         if (!message) return;

    //         axios.post('{{ route('chat.send') }}', {
    //             message,
    //             receiver_id: receiverId
    //         }, {
    //             headers: {
    //                 'X-CSRF-TOKEN': csrfToken
    //             }
    //         }).then(() => {
    //             input.value = '';
    //         }).catch(err => {
    //             alert('Message failed to send. Please try again.');
    //             console.error(err);
    //         });

    //         const isSelf = true;// data.sender_id === currentId;

    //         const li = document.createElement("li");
    //         li.classList.add('message');

    //         const bubble = document.createElement("div");
    //         bubble.classList.add('bubble', isSelf ? 'bubble-right' : 'bubble-left');
    //         bubble.textContent = DOMPurify.sanitize(message);

    //         li.appendChild(bubble);
    //         document.getElementById("messages").appendChild(li);

    //         // Scroll down
    //         const messages = document.getElementById('messages');
    //         messages.scrollTop = messages.scrollHeight;
    //     }
    // }

    function showOnlineUsers(currentUserId) {

        window.Echo.join('presence.online')
            .here(users => {
                const filteredUsers = users.filter(user => user.id !== currentUserId.id);
                console.log('users',filteredUsers)
                renderOnlineUsers(filteredUsers);
            })
            .joining(user => {
                addOnlineUser(user);
            })
            .leaving(user => {
                removeOnlineUser(user);
            });
    }

    function renderOnlineUsers(users) {
        const userList = document.getElementById('user-list');
        userList.innerHTML = ''; // Clear the list
        users.forEach(user => addOnlineUser(user));
    }

    function addOnlineUser(user) {
        const userList = document.getElementById('user-list');
        const li = document.createElement('li');
        li.classList.add('message'); // Keep styling consistent if needed
        li.innerHTML = `
            <div class="bubble bubble-left" style="cursor:pointer" onclick="window.location.href='?receiver=${user.id}'" role="button" tabindex="0" aria-label="Chat with ${DOMPurify.sanitize(user.name)}">
                👤 ${DOMPurify.sanitize(user.name)}
            </div>`;
        userList.appendChild(li);
    }

    function removeOnlineUser(user) {
        const userList = document.getElementById('user-list');
        const children = Array.from(userList.children);
        for (const child of children) {
            if (child.innerText.includes(user.name)) {
                child.remove();
            }
        }
    }

    // if (receiverId) {
    //     const currentUserId = await subscribeToChannel(receiverId);
    //     showOnlineUsers(currentUserId);
    // }

    (async () => {
        const responseSubscription = await subscribeToChannel(receiverId);
        const currentUser = responseSubscription.user;
        if (currentUser) {
            showOnlineUsers(currentUser); // Do something with currentUser
        }
    })();
});
</script>

@endsection
