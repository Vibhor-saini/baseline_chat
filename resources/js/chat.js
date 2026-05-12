(function(){
    function scrollToBottom(smooth=false){
        const anchor=document.getElementById('scroll-anchor');
        if(anchor){
            anchor.scrollIntoView({
                behavior:smooth?'smooth':'instant',
                block:'end'
            });
        }
    }
    document.addEventListener('livewire:navigated',()=>scrollToBottom());
    document.addEventListener('DOMContentLoaded',()=>scrollToBottom());
    document.addEventListener('livewire:init',()=>{
        let activeChannel=null;
        let activeConversationId=null;
        function connectToConversation(conversationId){
            if(!conversationId)return;
            if(activeChannel){
                Echo.leave(activeChannel);
            }
            activeChannel=`chat.${conversationId}`;
            Echo.channel(activeChannel)
            .listen('.message.sent',(event)=>{
                console.log('Realtime message received:',event);
                Livewire.find(
                    document.querySelector('[wire\\:id]').getAttribute('wire:id')
                ).call('appendMessage',event.message);
                setTimeout(()=>scrollToBottom(true),150);
            });
            activeConversationId=conversationId;
        }
        function detectConversation(){
            const root=document.querySelector('.teams-chat-root');
            if(!root)return;
            const conversationId=root.dataset.conversation;
            if(conversationId && conversationId!==activeConversationId){
                connectToConversation(conversationId);
            }
        }
        detectConversation();
        Livewire.hook('commit',({succeed})=>{
            succeed(()=>{
                requestAnimationFrame(()=>{
                    detectConversation();
                    scrollToBottom(true);
                });
            });
        });
    });
    document.addEventListener('click',(e)=>{
        const layout=document.querySelector('.teams-layout');
        if(!layout)return;
        if(e.target.closest('.conv-item')){
            layout.classList.add('conversation-open');
        }
        if(e.target.closest('#mobileBackBtn')){
            layout.classList.remove('conversation-open');
        }
    });
    document.addEventListener('livewire:update',()=>{
        const input=document.getElementById('messageInput');
        if(input && window.innerWidth>768){
            input.focus();
        }
    });
})();
(function(){
    const profileBtn=document.getElementById('profileBtn');
    const profileDropdown=document.getElementById('profileDropdown');
    const profileWrap=document.getElementById('profileWrap');
    function closeSearch(){
        const searchWrap=document.getElementById('globalSearchWrap');
        if(searchWrap){
            searchWrap.classList.remove('search-focused');
        }
    }
    if(profileBtn && profileDropdown){
        profileBtn.addEventListener('click',function(e){
            e.stopPropagation();
            const isOpen=profileDropdown.classList.toggle('dropdown-open');
            profileBtn.setAttribute('aria-expanded',isOpen?'true':'false');
            closeSearch();
        });
        document.addEventListener('click',function(e){
            if(!profileWrap.contains(e.target)){
                profileDropdown.classList.remove('dropdown-open');
                profileBtn.setAttribute('aria-expanded','false');
            }
        });
        document.addEventListener('keydown',function(e){
            if(e.key==='Escape'){
                profileDropdown.classList.remove('dropdown-open');
                profileBtn.setAttribute('aria-expanded','false');
            }
        });
    }
    const searchWrap=document.getElementById('globalSearchWrap');
    const searchInput=document.getElementById('globalSearchInput');
    if(searchInput){
        searchInput.addEventListener('focus',()=>{
            searchWrap.classList.add('search-focused');
        });
        document.addEventListener('click',function(e){
            if(!searchWrap.contains(e.target)){
                closeSearch();
            }
        });
        document.addEventListener('keydown',function(e){
            if((e.metaKey || e.ctrlKey) && e.key==='k'){
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
    }
    const teamsLayout=document.getElementById('teamsLayout');
    const backBtn=document.getElementById('mobileBackBtn');
    if(backBtn && teamsLayout){
        backBtn.addEventListener('click',()=>{
            teamsLayout.classList.remove('conversation-open');
        });
    }
    document.querySelectorAll('.conv-item').forEach(item=>{
        item.addEventListener('click',()=>{
            if(window.innerWidth<=768 && teamsLayout){
                teamsLayout.classList.add('conversation-open');
            }
        });
    });
    document.querySelectorAll('.status-item').forEach(btn=>{
        btn.addEventListener('click',function(){
            document.querySelectorAll('.status-item').forEach(b=>b.classList.remove('status-active'));
            this.classList.add('status-active');
        });
    });
    const anchor=document.getElementById('scroll-anchor');
    if(anchor){
        anchor.scrollIntoView({behavior:'smooth'});
    }
})();