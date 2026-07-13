<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Markdown from '../../Shared/Utils/Markdown.svelte';
    import ReasoningViewDrawer from './ReasoningViewDrawer.svelte';
    import { onMount, tick } from 'svelte';
    import { fade } from 'svelte/transition';

    const breadcrumbs = [
        { title: 'AI Agents', url: route('web.admin.chats.index'), active: true },
    ];

    const pageTitle = 'AI Agents';

    const AGENT_LABELS = {
        supervisor: 'Supervisor',
        purchasing: 'Purchasing',
        purchase_order: 'PO Agent',
        shipping: 'Shipping',
        warehouse: 'Warehouse',
        wh_good_receipt: 'WH Receipt',
        qc: 'QC',
        qa: 'QA',
        qp: 'QP',
        planning: 'Planning',
    };

    let chats = [];
    let pagination = {};
    let chatPage = 1;
    let perPage = 15;
    let loadingChats = true;
    let loadingMoreChats = false;

    let selectedChat = null;
    let messages = [];
    let loadingMessages = false;
    let isNewConversation = true;

    let messageInput = '';
    let sending = false;
    let liveStepOlder = null;
    let liveStepNewer = null;

    function pushLiveStep(step) {
        if (liveStepNewer) {
            liveStepOlder = liveStepNewer;
        }
        liveStepNewer = step;
    }

    function clearLiveSteps() {
        liveStepOlder = null;
        liveStepNewer = null;
    }

    function liveStepIcon(stepType) {
        switch (stepType) {
            case 'agent_start': return 'fa-play text-blue-500';
            case 'llm_call': return 'fa-brain text-violet-500';
            case 'tool_call': return 'fa-wrench text-amber-500';
            case 'tool_result': return 'fa-check text-green-500';
            case 'delegate': return 'fa-share-nodes text-cyan-500';
            case 'agent_end': return 'fa-stop text-slate-500';
            default: return 'fa-circle text-muted-foreground';
        }
    }
    let uploadingFile = false;
    let fileInputEl = null;
    let isRecording = false;
    let speechRecognition = null;
    let wantsVoiceInput = false;
    let isVoiceStarting = false;
    let voiceStartWatchdog = null;

    function voiceLog(...args) {
        console.log('[voice]', ...args);
    }

    let approvingEmailId = null;
    let rejectingEmailId = null;
    let approvingProposalId = null;
    let rejectingProposalId = null;
    let messagesContainer;
    let selectedReasoningMessage = null;

    function sortChatMessages(msgs) {
        return [...(msgs || [])].sort((a, b) => {
            const timelineA = Number(a.metadata?.timeline_order ?? 0);
            const timelineB = Number(b.metadata?.timeline_order ?? 0);
            if (timelineA > 0 || timelineB > 0) {
                const posA = timelineA > 0 ? timelineA : Number.MAX_SAFE_INTEGER;
                const posB = timelineB > 0 ? timelineB : Number.MAX_SAFE_INTEGER;
                if (posA !== posB) return posA - posB;
            }
            const ta = new Date(a.created_at).getTime();
            const tb = new Date(b.created_at).getTime();
            if (ta !== tb) return ta - tb;
            if (a.type === 'plan_proposal' && b.type === 'plan_proposal') {
                const oa = Number(a.metadata?.sort_order ?? a.metadata?.option_index ?? 999);
                const ob = Number(b.metadata?.sort_order ?? b.metadata?.option_index ?? 999);
                if (oa !== ob) return oa - ob;
            }
            return String(a.id).localeCompare(String(b.id));
        });
    }

    function setMessagesFromApi(rawMessages) {
        messages = sortChatMessages(rawMessages);
    }

    function getQueryChatId() {
        return new URLSearchParams(window.location.search).get('chat');
    }

    function setQueryChatId(chatId) {
        const url = new URL(window.location.href);

        if (chatId) {
            url.searchParams.set('chat', chatId);
        } else {
            url.searchParams.delete('chat');
        }

        window.history.replaceState({}, '', url);
    }

    async function approveDraftEmail(message) {
        const emailId = message?.metadata?.email_id;
        if (!emailId || approvingEmailId) return;

        const to = message.metadata?.to_name || message.metadata?.to_email || 'recipient';
        const confirmed = confirm(`Approve and send this email to ${to}?`);
        if (!confirmed) return;

        approvingEmailId = emailId;

        try {
            const response = await fetch(route('api.v1.admin.emails.approve', { email: emailId }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            });
            const data = await response.json();

            if (response.ok) {
                toast('Email approved and sent', 'success');
                await reloadCurrentChat();
            } else {
                toast(data.message || 'Failed to approve email', 'error');
            }
        } catch (error) {
            console.error('Error approving email:', error);
            toast('Network error occurred', 'error');
        } finally {
            approvingEmailId = null;
        }
    }

    async function rejectDraftEmail(message) {
        const emailId = message?.metadata?.email_id;
        if (!emailId || rejectingEmailId) return;

        const confirmed = confirm('Reject this email draft? It will not be sent.');
        if (!confirmed) return;

        rejectingEmailId = emailId;

        try {
            const response = await fetch(route('api.v1.admin.emails.reject', { email: emailId }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            });
            const data = await response.json();

            if (response.ok) {
                toast('Email draft rejected', 'success');
                await reloadCurrentChat();
            } else {
                toast(data.message || 'Failed to reject email', 'error');
            }
        } catch (error) {
            console.error('Error rejecting email:', error);
            toast('Network error occurred', 'error');
        } finally {
            rejectingEmailId = null;
        }
    }

    async function approvePlanProposal(message) {
        const proposalId = message?.metadata?.proposal_id;
        if (!proposalId || approvingProposalId) return;

        const confirmed = confirm(`Apply this plan change: ${message.metadata?.title || 'proposal'}?`);
        if (!confirmed) return;

        approvingProposalId = proposalId;

        try {
            const response = await fetch(route('api.v1.admin.production-plan-proposals.approve', { proposal: proposalId }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            });
            const data = await response.json();

            if (response.ok) {
                toast('Plan change approved and applied', 'success');
                await reloadCurrentChat();
            } else {
                toast(data.message || 'Failed to approve plan change', 'error');
            }
        } catch (error) {
            console.error('Error approving plan proposal:', error);
            toast('Network error occurred', 'error');
        } finally {
            approvingProposalId = null;
        }
    }

    async function rejectPlanProposal(message) {
        const proposalId = message?.metadata?.proposal_id;
        if (!proposalId || rejectingProposalId) return;

        const confirmed = confirm('Reject this plan change proposal? Plans will not be modified.');
        if (!confirmed) return;

        rejectingProposalId = proposalId;

        try {
            const response = await fetch(route('api.v1.admin.production-plan-proposals.reject', { proposal: proposalId }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            });
            const data = await response.json();

            if (response.ok) {
                toast('Plan change proposal rejected', 'success');
                await reloadCurrentChat();
            } else {
                toast(data.message || 'Failed to reject plan proposal', 'error');
            }
        } catch (error) {
            console.error('Error rejecting plan proposal:', error);
            toast('Network error occurred', 'error');
        } finally {
            rejectingProposalId = null;
        }
    }

    function formatAgent(agent) {
        if (!agent) return 'Agent';
        return AGENT_LABELS[agent] || agent.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }

    function formatChatTitle(chat) {
        return chat?.title || 'New Chat';
    }

    function formatTimeAgo(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        return `${Math.floor(diffInSeconds / 86400)}d ago`;
    }

    function ensureSelectedChatInSidebar() {
        if (!selectedChat) return;

        const exists = chats.some((chat) => chat.id === selectedChat.id);
        if (!exists) {
            chats = [selectedChat, ...chats];
        }
    }

    async function fetchChats(reset = true, chatId = null) {
        if (reset) {
            chatPage = 1;
            loadingChats = true;
        } else {
            loadingMoreChats = true;
        }

        try {
            const params = {
                page: chatPage,
                per_page: chatId ? 1 : perPage,
                sort_direction: 'desc',
            };

            if (chatId) {
                params.search = chatId;
            }

            const response = await fetch(route('api.v1.admin.chats.index', params), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();

            if (reset) {
                chats = data.chats || [];
            } else {
                chats = [...chats, ...(data.chats || [])];
            }
            pagination = data.pagination || {};

            if (!chatId) {
                ensureSelectedChatInSidebar();
            }

            return chats;
        } catch (error) {
            console.error('Error fetching chats:', error);
            return [];
        } finally {
            loadingChats = false;
            loadingMoreChats = false;
        }
    }

    async function loadMoreChats() {
        if (loadingMoreChats || loadingChats) return;
        if (!pagination || chatPage >= (pagination.last_page || 1)) return;

        chatPage += 1;
        await fetchChats(false);
    }

    function startNewConversation() {
        selectedChat = null;
        messages = [];
        isNewConversation = true;
        messageInput = '';
        setQueryChatId(null);
    }

    async function reloadCurrentChat() {
        if (!selectedChat) return;

        loadingMessages = true;

        try {
            const response = await fetch(route('api.v1.admin.chats.show', selectedChat.id), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();
            selectedChat = data.chat;
            setMessagesFromApi(data.messages);
            await scrollToLatestMessageAfterUpdate(false);
        } catch (error) {
            console.error('Error reloading chat:', error);
        } finally {
            loadingMessages = false;
        }
    }

    async function selectChat(chat) {
        if (selectedChat?.id === chat.id) return;

        selectedChat = chat;
        isNewConversation = false;
        loadingMessages = true;
        messages = [];
        setQueryChatId(chat.id);

        try {
            const response = await fetch(route('api.v1.admin.chats.show', chat.id), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();
            selectedChat = data.chat;
            setMessagesFromApi(data.messages);
            await scrollToLatestMessageAfterUpdate(false);
        } catch (error) {
            console.error('Error loading chat:', error);
            toast('Failed to load conversation', 'error');
        } finally {
            loadingMessages = false;
        }
    }

    async function openChatFromChatId(chatId) {
        loadingMessages = true;
        messages = [];

        try {
            const response = await fetch(route('api.v1.admin.chats.show', chatId), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                toast('Conversation not found', 'error');
                setQueryChatId(null);
                await fetchChats(true);
                startNewConversation();
                return;
            }

            const data = await response.json();
            selectedChat = data.chat;
            setMessagesFromApi(data.messages);
            isNewConversation = false;
            setQueryChatId(chatId);

            await fetchChats(true);
            ensureSelectedChatInSidebar();

            await scrollToLatestMessageAfterUpdate(false);
        } catch (error) {
            console.error('Error opening chat from URL:', error);
            toast('Failed to load conversation', 'error');
            setQueryChatId(null);
            await fetchChats(true);
            startNewConversation();
        } finally {
            loadingMessages = false;
        }
    }

    function notifyToast(message, variant = 'info') {
        if (typeof window.toast === 'function') {
            window.toast(message, variant);
        }
    }

    async function consumeAgentStream(response) {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('text/event-stream')) {
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Failed to send message');
            }
            return data;
        }

        const reader = response.body?.getReader();
        if (!reader) {
            throw new Error('Streaming is not supported in this browser.');
        }

        const decoder = new TextDecoder();
        let buffer = '';
        let result = null;

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const parts = buffer.split('\n\n');
            buffer = parts.pop() || '';

            for (const part of parts) {
                const line = part.trim();
                if (!line.startsWith('data:')) continue;

                const payload = JSON.parse(line.slice(5).trim());

                if (payload.type === 'step') {
                    pushLiveStep(payload.step);
                } else if (payload.type === 'done') {
                    result = payload;
                } else if (payload.type === 'error') {
                    throw new Error(payload.message || 'The assistant could not respond.');
                }
            }

            if (liveStepNewer) {
                await tick();
                scrollToLatestMessage(false);
            }
        }

        if (!result) {
            throw new Error('The assistant did not return a response.');
        }

        return result;
    }

    function clearVoiceStartWatchdog() {
        if (voiceStartWatchdog) {
            clearTimeout(voiceStartWatchdog);
            voiceStartWatchdog = null;
        }
    }

    function startSpeechRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition || !wantsVoiceInput) {
            voiceLog('startSpeechRecognition skipped', {
                hasApi: !!SpeechRecognition,
                wantsVoiceInput,
            });
            return;
        }

        voiceLog('starting recognition session', {
            lang: navigator.language || 'en-US',
            secureContext: window.isSecureContext,
            protocol: window.location.protocol,
            isOpera: /OPR\//.test(navigator.userAgent),
        });

        const recognition = new SpeechRecognition();
        recognition.lang = navigator.language || 'en-US';
        recognition.interimResults = true;
        recognition.continuous = true;
        recognition.maxAlternatives = 1;

        const sessionBase = messageInput.trim();
        let committedInSession = '';
        let pendingInterim = '';

        recognition.onstart = () => {
            clearVoiceStartWatchdog();
            isVoiceStarting = false;
            voiceLog('onstart — microphone active, listening');
            notifyToast('Listening… speak now. Click mic again to stop.', 'info');
            isRecording = true;
        };

        recognition.onaudiostart = () => voiceLog('onaudiostart — audio capture began');
        recognition.onaudioend = () => voiceLog('onaudioend — audio capture ended');
        recognition.onsoundstart = () => voiceLog('onsoundstart — sound detected');
        recognition.onsoundend = () => voiceLog('onsoundend — sound ended');
        recognition.onspeechstart = () => voiceLog('onspeechstart — speech detected');
        recognition.onspeechend = () => voiceLog('onspeechend — speech ended');
        recognition.onnomatch = () => voiceLog('onnomatch — heard audio but no transcription');

        recognition.onresult = (event) => {
            let interim = '';
            let finals = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const result = event.results[i];
                const text = result[0]?.transcript || '';
                if (result.isFinal) {
                    finals += text;
                } else {
                    interim += text;
                }
            }

            voiceLog('onresult', {
                resultIndex: event.resultIndex,
                resultsLength: event.results.length,
                interim: interim || null,
                final: finals || null,
            });

            if (finals.trim()) {
                committedInSession = committedInSession
                    ? `${committedInSession} ${finals.trim()}`
                    : finals.trim();
                pendingInterim = '';
            } else if (interim) {
                pendingInterim = interim;
            }

            const prefix = sessionBase
                ? (committedInSession ? `${sessionBase} ${committedInSession}` : sessionBase)
                : committedInSession;

            messageInput = pendingInterim
                ? (prefix ? `${prefix} ${pendingInterim}` : pendingInterim)
                : prefix;

            voiceLog('messageInput updated', { length: messageInput.length, preview: messageInput.slice(0, 80) });
        };

        recognition.onerror = (event) => {
            clearVoiceStartWatchdog();
            isVoiceStarting = false;

            voiceLog('onerror', {
                error: event.error,
                message: event.message || null,
            });

            if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                notifyToast('Microphone permission denied.', 'error');
                wantsVoiceInput = false;
            } else if (event.error === 'network') {
                notifyToast('Voice service unreachable. Try Chrome or check your connection.', 'error');
                wantsVoiceInput = false;
            } else if (event.error === 'aborted') {
                voiceLog('recognition aborted');
            } else if (event.error === 'no-speech') {
                voiceLog('no speech detected this session');
            } else {
                notifyToast(`Voice input error: ${event.error}`, 'error');
            }
        };

        recognition.onend = () => {
            clearVoiceStartWatchdog();
            isVoiceStarting = false;
            isRecording = false;
            speechRecognition = null;

            voiceLog('onend', {
                wantsVoiceInput,
                committedInSession: committedInSession || null,
                pendingInterim: pendingInterim || null,
            });

            if (pendingInterim.trim()) {
                committedInSession = committedInSession
                    ? `${committedInSession} ${pendingInterim.trim()}`
                    : pendingInterim.trim();
            }

            if (committedInSession) {
                messageInput = sessionBase
                    ? `${sessionBase} ${committedInSession}`.trim()
                    : committedInSession;
            }

            // Only the mic button toggles recording — never auto-restart.
            if (wantsVoiceInput) {
                voiceLog('browser ended session unexpectedly — resetting mic state');
                wantsVoiceInput = false;
            }
        };

        speechRecognition = recognition;

        try {
            recognition.start();
            voiceLog('recognition.start() called — waiting for onstart…');

            clearVoiceStartWatchdog();
            voiceStartWatchdog = setTimeout(() => {
                voiceLog('WATCHDOG: onstart never fired within 6s', {
                    hint: 'Opera/HTTP often blocks Web Speech API. Try Google Chrome, or use https://localhost',
                });
                isVoiceStarting = false;
                wantsVoiceInput = false;
                try {
                    recognition.abort();
                } catch {
                    recognition.stop();
                }
                notifyToast('Voice input did not start. Try Chrome instead of Opera.', 'error');
            }, 6000);
        } catch (error) {
            clearVoiceStartWatchdog();
            isVoiceStarting = false;
            voiceLog('recognition.start() threw', error);
            wantsVoiceInput = false;
            isRecording = false;
            speechRecognition = null;
            notifyToast('Could not start voice input. Try again.', 'error');
        }
    }

    async function toggleVoiceInput() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        voiceLog('mic clicked', {
            hasApi: !!SpeechRecognition,
            wantsVoiceInput,
            isRecording,
            isVoiceStarting,
            secureContext: window.isSecureContext,
            protocol: window.location.protocol,
            userAgent: navigator.userAgent,
        });

        if (/OPR\//.test(navigator.userAgent)) {
            voiceLog('Opera detected — Web Speech API is unreliable in Opera; Chrome is recommended');
        }

        if (!SpeechRecognition) {
            voiceLog('SpeechRecognition API not available in this browser');
            notifyToast('Voice input is not supported in this browser. Try Chrome or Edge.', 'error');
            return;
        }

        if (wantsVoiceInput) {
            voiceLog('stopping — user clicked mic again');
            wantsVoiceInput = false;
            clearVoiceStartWatchdog();
            isVoiceStarting = false;
            speechRecognition?.stop();
            return;
        }

        wantsVoiceInput = true;
        isVoiceStarting = true;
        notifyToast('Requesting microphone access…', 'info');

        try {
            if (navigator.permissions?.query) {
                const perm = await navigator.permissions.query({ name: 'microphone' });
                voiceLog('microphone permission state', perm.state);
            }
        } catch (error) {
            voiceLog('could not query microphone permission', error);
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            voiceLog('getUserMedia not available');
            wantsVoiceInput = false;
            isVoiceStarting = false;
            notifyToast('Microphone API not available in this browser.', 'error');
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            voiceLog('getUserMedia granted — releasing tracks before SpeechRecognition');
            stream.getTracks().forEach((track) => track.stop());
        } catch (error) {
            voiceLog('getUserMedia denied or failed', error);
            wantsVoiceInput = false;
            isVoiceStarting = false;
            notifyToast('Microphone permission denied.', 'error');
            return;
        }

        startSpeechRecognition();
    }

    function triggerFileUpload() {
        if (!selectedChat || isNewConversation) {
            toast('Start a conversation first before attaching a file.', 'info');
            return;
        }
        fileInputEl?.click();
    }

    async function handleFileSelected(event) {
        const file = event.target.files?.[0];
        if (!file || !selectedChat) return;

        event.target.value = '';

        const ext = file.name.split('.').pop()?.toLowerCase();
        if (!['csv', 'pdf'].includes(ext)) {
            toast('Only CSV and PDF files are supported.', 'error');
            return;
        }

        uploadingFile = true;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));

        try {
            const response = await fetch(
                route('api.v1.admin.chats.attachments.store', { chat: selectedChat.id }),
                {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                },
            );
            const data = await response.json();

            if (!response.ok) {
                toast(data.message || 'Failed to attach file.', 'error');
                return;
            }

            messages = sortChatMessages([...messages, data.message]);
            await tick();
            scrollToLatestMessage(true);
        } catch {
            toast('Network error uploading file.', 'error');
        } finally {
            uploadingFile = false;
        }
    }

    async function sendMessage() {
        const body = messageInput.trim();
        if (!body || sending) return;

        if (wantsVoiceInput) {
            wantsVoiceInput = false;
            clearVoiceStartWatchdog();
            isVoiceStarting = false;
            speechRecognition?.stop();
        }

        sending = true;
        clearLiveSteps();
        const previousInput = messageInput;
        messageInput = '';

        await tick();
        scrollToLatestMessage(true);

        const streamHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'text/event-stream',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        };

        try {
            const isNew = isNewConversation || !selectedChat;
            const url = isNew
                ? route('api.v1.admin.chats.store')
                : route('api.v1.admin.chats.messages.store', selectedChat.id);

            const response = await fetch(url, {
                method: 'POST',
                headers: streamHeaders,
                body: JSON.stringify({ message: body }),
            });

            const data = await consumeAgentStream(response);

            selectedChat = data.chat;
            setMessagesFromApi(data.messages);
            isNewConversation = false;
            setQueryChatId(selectedChat.id);

            await fetchChats(true);

            const refreshed = chats.find((c) => c.id === selectedChat.id);
            if (refreshed) {
                selectedChat = refreshed;
            }

            await scrollToLatestMessageAfterUpdate(true);
        } catch (error) {
            console.error('Error sending message:', error);
            messageInput = previousInput;
            notifyToast(error.message || 'Network error occurred', 'error');
        } finally {
            sending = false;
            clearLiveSteps();
        }
    }

    function handleKeydown(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    }

    function scrollContainerToElement(element, smooth = true) {
        if (!element || !messagesContainer) return;

        const containerRect = messagesContainer.getBoundingClientRect();
        const elementRect = element.getBoundingClientRect();
        const offset = elementRect.top - containerRect.top + messagesContainer.scrollTop - 12;

        messagesContainer.scrollTo({
            top: Math.max(0, offset),
            behavior: smooth ? 'smooth' : 'auto',
        });
    }

    function scrollToLatestMessage(smooth = true) {
        requestAnimationFrame(() => {
            const nodes = messagesContainer?.querySelectorAll('[data-message-anchor]');
            const last = nodes?.[nodes.length - 1];

            if (last) {
                scrollContainerToElement(last, smooth);
            }
        });
    }

    function scrollToFirstPendingProposal(smooth = true) {
        requestAnimationFrame(() => {
            const pending = messages.find(
                (m) => m.type === 'plan_proposal' && m.metadata?.can_be_approved
            );

            if (!pending) {
                scrollToLatestMessage(smooth);
                return;
            }

            const node = messagesContainer?.querySelector(
                `[data-message-id="${pending.id}"]`
            );

            if (node) {
                scrollContainerToElement(node, smooth);
            } else {
                scrollToLatestMessage(smooth);
            }
        });
    }

    async function scrollToLatestMessageAfterUpdate(smooth = true) {
        await tick();

        const hasPendingPlan = messages.some(
            (m) => m.type === 'plan_proposal' && m.metadata?.can_be_approved
        );

        if (hasPendingPlan) {
            scrollToFirstPendingProposal(smooth);
            return;
        }

        scrollToLatestMessage(smooth);
    }

    function openReasoningDrawer(message) {
        selectedReasoningMessage = message;
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#reasoning_view_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    onMount(async () => {
        const chatId = getQueryChatId();

        if (chatId) {
            await openChatFromChatId(chatId);
        } else {
            await fetchChats(true);
            startNewConversation();
        }
    });
</script>

<svelte:head>
    <title>Novonordisk supply chain management system - {pageTitle}</title>
</svelte:head>

<MainLayout {breadcrumbs}>
    <div class="kt-card flex flex-col lg:flex-row overflow-hidden min-h-0 chats-card">
        <!-- Conversations sidebar -->
        <aside class="w-full lg:w-80 shrink-0 border-b lg:border-b-0 lg:border-e border-border flex flex-col min-h-0 lg:max-h-none chats-sidebar">
            <div class="px-4 py-4 border-b border-border shrink-0">
                <button
                    type="button"
                    class="kt-btn kt-btn-primary w-full justify-center"
                    on:click={startNewConversation}
                >
                    <i class="fa-solid fa-plus mr-2"></i>
                    New conversation
                </button>
            </div>

            <div class="chats-sidebar-scroll grow min-h-0 overflow-y-auto overscroll-y-contain" style="overflow-y: auto;">
                {#if loadingChats}
                    <div class="flex flex-col w-full">
                        {#each Array(8) as _, i}
                            <div class="w-full border-b border-border px-4 py-3">
                                <div class="kt-skeleton h-3.5 rounded" style="width: {60 + (i % 3) * 10}%;"></div>
                            </div>
                        {/each}
                    </div>
                {:else if chats.length === 0}
                    <div class="p-4 text-center text-sm text-muted-foreground">
                        No conversations yet.
                    </div>
                {:else}
                    <div class="flex flex-col gap-1">
                        {#each chats as chat (chat.id)}
                            <button
                                type="button"
                                class="w-full cursor-pointer text-left px-3 py-2 transition-colors hover:bg-muted border-b border-border {selectedChat?.id === chat.id ? 'bg-primary/10' : ''}"
                                on:click={() => selectChat(chat)}
                            >
                                <div class="text-sm font-medium text-mono line-clamp-1">
                                    {formatChatTitle(chat)}
                                </div>

                                <div class="text-xs text-muted-foreground line-clamp-1 mt-0.5">
                                    {formatTimeAgo(chat.updated_at)}
                                </div>
                            </button>
                        {/each}
                    </div>

                    {#if pagination && chatPage < (pagination.last_page || 1)}
                        <div class="p-3">
                            <button
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-ghost w-full justify-center"
                                on:click={loadMoreChats}
                                disabled={loadingMoreChats}
                            >
                                {#if loadingMoreChats}
                                    <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                                    Loading...
                                {:else}
                                    Load more
                                {/if}
                            </button>
                        </div>
                    {/if}
                {/if}
            </div>
        </aside>

        <!-- Chat panel -->
        <section class="flex flex-col flex-1 min-w-0 min-h-0 overflow-hidden chats-panel">
            {#if isNewConversation && !selectedChat}
                <div class="px-5 py-4 border-b border-border shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center size-10 rounded-full bg-primary/10">
                            <i class="fa-solid fa-robot text-primary"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-mono">New conversation</h2>
                            <p class="text-xs text-muted-foreground">Ask the supervisor agent anything about supply chain operations.</p>
                        </div>
                    </div>
                </div>
            {:else if selectedChat}
                <div class="px-5 py-4 border-b border-border shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <img
                            src={getAuthUser().avatar_url}
                            alt={getAuthUser().name}
                            class="size-10 rounded-full border-2 border-green-500 shrink-0"
                        />
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold text-mono line-clamp-1">
                                {formatChatTitle(selectedChat)}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {getAuthUser().name} · Supervisor agent
                            </p>
                        </div>
                    </div>
                </div>
            {/if}

            <div
                class="chats-messages flex-1 min-h-0 px-5 py-4" style="overflow-y: auto;"
                bind:this={messagesContainer}
            >
                {#if loadingMessages}
                    <div class="flex flex-col gap-4 max-w-3xl mx-auto w-full">
                        <div class="flex gap-3 justify-end items-start">
                            <div class="flex flex-col gap-2 items-end flex-1 max-w-[85%]">
                                <div class="kt-skeleton h-10 w-40 rounded-xl rounded-tr-sm"></div>
                            </div>
                            <div class="kt-skeleton size-8 rounded-full shrink-0"></div>
                        </div>
                        <div class="flex gap-3 justify-start items-start">
                            <div class="kt-skeleton size-8 rounded-full shrink-0"></div>
                            <div class="flex flex-col gap-2 flex-1 max-w-[85%]">
                                <div class="kt-skeleton h-3 w-20 rounded"></div>
                                <div class="kt-skeleton h-24 w-full rounded-xl rounded-tl-sm"></div>
                            </div>
                        </div>
                        <div class="flex gap-3 justify-end items-start">
                            <div class="flex flex-col gap-2 items-end flex-1 max-w-[85%]">
                                <div class="kt-skeleton h-14 w-52 rounded-xl rounded-tr-sm"></div>
                            </div>
                            <div class="kt-skeleton size-8 rounded-full shrink-0"></div>
                        </div>
                        <div class="flex gap-3 justify-start items-start">
                            <div class="kt-skeleton size-8 rounded-full shrink-0"></div>
                            <div class="flex flex-col gap-2 flex-1 max-w-[85%]">
                                <div class="kt-skeleton h-3 w-20 rounded"></div>
                                <div class="kt-skeleton h-32 w-full rounded-xl rounded-tl-sm"></div>
                            </div>
                        </div>
                    </div>
                {:else if messages.length === 0}
                    <div class="flex flex-col items-center justify-center h-full text-center py-10">
                        <div class="flex items-center justify-center size-16 rounded-full bg-primary/10 mb-4">
                            <i class="fa-solid fa-robot text-2xl text-primary"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-mono mb-2">Supervisor Agent</h3>
                        <p class="text-sm text-muted-foreground max-w-md">
                            Coordinate purchasing, production, and planning questions.
                        </p>
                    </div>
                {:else}
                    <div class="flex flex-col gap-4 max-w-3xl mx-auto" style="overflow-y: auto;">
                        {#each messages as message (message.id)}
                            {#if message.type === 'received_mail'}
                                <div
                                    class="flex gap-3 justify-start scroll-mt-4"
                                    data-message-anchor
                                    data-message-id={message.id}
                                >
                                    <div class="flex items-center justify-center size-8 rounded-full bg-amber-500/15 shrink-0 mt-0.5">
                                        <i class="fa-solid fa-envelope text-amber-600 text-xs"></i>
                                    </div>
                                    <div class="max-w-[85%] rounded-xl rounded-tl-sm border border-amber-500/30 bg-amber-500/5 px-3 py-2.5">
                                        <div class="text-[11px] font-semibold text-amber-700 mb-1">Received email</div>
                                        <Markdown content={message.body} />
                                    </div>
                                </div>
                            {:else if message.type === 'document'}
                                <div
                                    class="flex gap-3 justify-end scroll-mt-4"
                                    data-message-anchor
                                    data-message-id={message.id}
                                >
                                    <div class="max-w-[85%] rounded-xl rounded-tr-sm border border-violet-500/30 bg-violet-500/5 px-3 py-2.5">
                                        <div class="flex items-center gap-2 text-[11px] font-semibold text-violet-700 mb-1">
                                            <i class="fa-solid fa-file-{message.metadata?.extension === 'pdf' ? 'pdf' : 'csv'} text-violet-500"></i>
                                            Attached document
                                        </div>
                                        <div class="text-sm font-medium text-mono">{message.metadata?.original_name || 'document'}</div>
                                        <div class="text-xs text-muted-foreground mt-0.5">
                                            {message.metadata?.extension?.toUpperCase()} · {((message.metadata?.char_count || 0) / 1000).toFixed(1)}k characters extracted
                                        </div>
                                    </div>
                                    <img
                                        src={message.user?.avatar_url || getAuthUser().avatar_url}
                                        alt="You"
                                        class="size-8 rounded-full border border-border shrink-0"
                                    />
                                </div>
                            {:else if message.type === 'draft_email'}
                                <div
                                    class="flex gap-3 justify-start scroll-mt-4"
                                    data-message-anchor
                                    data-message-id={message.id}
                                >
                                    <div class="flex items-center justify-center size-8 rounded-full bg-blue-500/15 shrink-0 mt-0.5">
                                        <i class="fa-solid fa-paper-plane text-blue-600 text-xs"></i>
                                    </div>
                                    <div style="max-width: 85%;" class="rounded-xl rounded-tl-sm border border-blue-500/30 bg-blue-500/5 px-3 py-2.5">
                                        <div class="text-[11px] font-semibold text-blue-700 mb-1">
                                            {#if message.metadata?.status === 'sent'}
                                                Email sent
                                            {:else if message.metadata?.status === 'rejected'}
                                                Email draft rejected
                                            {:else}
                                                Draft email · {formatAgent(message.metadata?.source_agent || message.agent)} · awaiting approval
                                            {/if}
                                        </div>
                                        <div class="text-sm font-medium mb-1">{message.metadata?.subject || '(no subject)'}</div>
                                        <div class="text-xs text-muted-foreground mb-2">
                                            To: {message.metadata?.to_name || message.metadata?.to_email}
                                            {#if message.metadata?.supplier_name}
                                                · {message.metadata.supplier_name}
                                            {/if}
                                        </div>
                                        <pre style="white-space: pre-wrap;" class="font-mono text-xs bg-background/60 rounded-lg p-3 border border-border max-h-48 overflow-y-auto">{message.metadata?.body}</pre>
                                        {#if message.metadata?.can_be_approved}
                                            <div class="flex gap-2 mt-3 pt-3 border-t border-blue-500/20">
                                                <button
                                                    type="button"
                                                    class="kt-btn kt-btn-sm kt-btn-primary"
                                                    on:click={() => approveDraftEmail(message)}
                                                    disabled={approvingEmailId === message.metadata?.email_id || rejectingEmailId === message.metadata?.email_id}
                                                >
                                                    {#if approvingEmailId === message.metadata?.email_id}
                                                        <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                                                    {:else}
                                                        <i class="fa-solid fa-check mr-1"></i>
                                                    {/if}
                                                    Approve &amp; send
                                                </button>
                                                <button
                                                    type="button"
                                                    class="kt-btn kt-btn-sm kt-btn-outline"
                                                    on:click={() => rejectDraftEmail(message)}
                                                    disabled={approvingEmailId === message.metadata?.email_id || rejectingEmailId === message.metadata?.email_id}
                                                >
                                                    {#if rejectingEmailId === message.metadata?.email_id}
                                                        <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                                                    {:else}
                                                        <i class="fa-solid fa-xmark mr-1"></i>
                                                    {/if}
                                                    Reject
                                                </button>
                                            </div>
                                        {/if}
                                    </div>
                                </div>
                            {:else if message.type === 'plan_proposal'}
                                <div
                                    class="flex gap-3 justify-start scroll-mt-4"
                                    data-message-anchor
                                    data-message-id={message.id}
                                >
                                    <div class="flex items-center justify-center size-8 rounded-full bg-emerald-500/15 shrink-0 mt-0.5">
                                        <i class="fa-solid fa-calendar-check text-emerald-600 text-xs"></i>
                                    </div>
                                    <div style="max-width: 85%;" class="rounded-xl rounded-tl-sm border border-emerald-500/30 bg-emerald-500/5 px-3 py-2.5">
                                        <div class="text-[11px] font-semibold text-emerald-700 mb-1">
                                            {#if message.metadata?.status === 'applied'}
                                                Plan change applied
                                            {:else if message.metadata?.status === 'rejected'}
                                                Plan change rejected
                                            {:else}
                                                Plan change · {formatAgent(message.metadata?.source_agent || message.agent)} · awaiting approval
                                            {/if}
                                        </div>
                                        <div class="text-sm font-medium mb-1">{message.metadata?.title || '(no title)'}</div>
                                        <div class="text-xs text-muted-foreground mb-2">
                                            {#if message.metadata?.option_index}
                                                Option {message.metadata.option_index}
                                            {:else}
                                                Action: {message.metadata?.action_type}
                                            {/if}
                                        </div>
                                        {#if message.metadata?.superseded}
                                            <p class="text-xs text-muted-foreground italic mb-2">Cancelled — another option was approved.</p>
                                        {/if}
                                        {#if message.metadata?.summary}
                                            <Markdown content={message.metadata.summary} />
                                        {/if}
                                        {#if message.metadata?.can_be_approved && !message.metadata?.superseded}
                                            <div class="flex gap-2 mt-3 pt-3 border-t border-emerald-500/20">
                                                <button
                                                    type="button"
                                                    class="kt-btn kt-btn-sm kt-btn-primary"
                                                    on:click={() => approvePlanProposal(message)}
                                                    disabled={approvingProposalId === message.metadata?.proposal_id || rejectingProposalId === message.metadata?.proposal_id}
                                                >
                                                    {#if approvingProposalId === message.metadata?.proposal_id}
                                                        <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                                                    {:else}
                                                        <i class="fa-solid fa-check mr-1"></i>
                                                    {/if}
                                                    Approve &amp; apply
                                                </button>
                                                <button
                                                    type="button"
                                                    class="kt-btn kt-btn-sm kt-btn-outline"
                                                    on:click={() => rejectPlanProposal(message)}
                                                    disabled={approvingProposalId === message.metadata?.proposal_id || rejectingProposalId === message.metadata?.proposal_id}
                                                >
                                                    {#if rejectingProposalId === message.metadata?.proposal_id}
                                                        <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                                                    {:else}
                                                        <i class="fa-solid fa-xmark mr-1"></i>
                                                    {/if}
                                                    Reject
                                                </button>
                                            </div>
                                        {/if}
                                    </div>
                                </div>
                            {:else if message.type === 'notification_proposal'}
                                <div
                                    class="flex gap-3 justify-start scroll-mt-4"
                                    data-message-anchor
                                    data-message-id={message.id}
                                >
                                    <div class="flex items-center justify-center size-8 rounded-full bg-orange-500/15 shrink-0 mt-0.5">
                                        <i class="fa-solid fa-bell text-orange-600 text-xs"></i>
                                    </div>
                                    <div class="max-w-[85%] rounded-xl rounded-tl-sm border border-orange-500/30 bg-orange-500/5 px-3 py-2.5">
                                        <div class="text-[11px] font-semibold text-orange-700 mb-1">
                                            Notification proposal · {formatAgent(message.metadata?.source_agent || message.agent)}
                                        </div>
                                        <div class="text-sm font-medium mb-1">{message.metadata?.title}</div>
                                        <p class="text-xs text-muted-foreground whitespace-pre-wrap">{message.metadata?.message}</p>
                                        <p class="text-[11px] text-muted-foreground mt-2 italic">For the supervisor to send — no action here. Plan changes use the green cards below with Approve &amp; apply.</p>
                                    </div>
                                </div>
                            {:else if message.sender_type === 'user'}
                                <div
                                    class="flex gap-3 justify-end scroll-mt-4"
                                    data-message-anchor
                                    data-message-id={message.id}
                                >
                                    <div class="max-w-[85%] rounded-xl rounded-tr-sm bg-primary text-primary-foreground px-3 py-2.5">
                                        <p class="text-xs leading-relaxed whitespace-pre-wrap break-words">{message.body}</p>
                                    </div>
                                    <img
                                        src={message.user?.avatar_url || getAuthUser().avatar_url}
                                        alt="You"
                                        class="size-8 rounded-full border border-border shrink-0"
                                    />
                                </div>
                            {:else}
                                <div
                                    class="flex gap-3 justify-start scroll-mt-4"
                                    data-message-anchor
                                    data-message-id={message.id}
                                >
                                    <div class="flex items-center justify-center size-8 rounded-full bg-primary/10 shrink-0 mt-0.5">
                                        <i class="fa-solid fa-robot text-primary text-xs"></i>
                                    </div>
                                    <div class="max-w-[85%] rounded-xl rounded-tl-sm border border-border bg-muted/40 px-3 py-2.5">
                                        <div class="flex items-center justify-between gap-2 mb-1.5">
                                            <div class="text-[11px] font-semibold text-muted-foreground">
                                                {formatAgent(message.agent)}
                                            </div>
                                            <button
                                                type="button"
                                                class="kt-btn kt-btn-xs kt-btn-ghost shrink-0 h-6 px-2"
                                                on:click={() => openReasoningDrawer(message)}
                                                title="View reasoning trace"
                                            >
                                                <i class="fa-solid fa-diagram-project mr-1"></i>
                                                View
                                            </button>
                                        </div>
                                        <Markdown content={message.body} />
                                    </div>
                                </div>
                            {/if}
                        {/each}

                        {#if sending}
                            <div class="flex gap-3 justify-start scroll-mt-4" data-message-anchor>
                                <div class="flex items-center justify-center size-8 rounded-full bg-primary/10 shrink-0 mt-0.5">
                                    <i class="fa-solid fa-robot text-primary text-xs"></i>
                                </div>
                                <div class="max-w-[85%] rounded-xl rounded-tl-sm border border-border bg-muted/40 px-3 py-2.5 space-y-2 min-w-[280px]">
                                    <div class="flex items-center gap-1.5 text-[11px] font-semibold text-muted-foreground mb-1">
                                        <i class="fa-solid fa-spinner fa-spin text-primary text-[10px]"></i>
                                        Thinking…
                                    </div>
                                    {#if !liveStepNewer}
                                        <div class="text-xs text-muted-foreground" in:fade={{ duration: 250 }}>Starting supervisor agent…</div>
                                    {:else}
                                        <div class="live-steps-window space-y-2 min-h-[3.25rem]">
                                            {#if liveStepOlder}
                                                <div class="flex items-start gap-2 live-step-row live-step-row--older">
                                                    <div class="flex items-center justify-center size-5 rounded-full bg-background border border-border shrink-0 mt-0.5">
                                                        <i class="fa-solid {liveStepIcon(liveStepOlder.step_type)} text-[8px]"></i>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="text-xs text-mono leading-snug">{liveStepOlder.title}</div>
                                                        {#if liveStepOlder.tool_name}
                                                            <span class="text-[10px] font-mono text-amber-600">{liveStepOlder.tool_name}</span>
                                                        {/if}
                                                        {#if liveStepOlder.model}
                                                            <span class="text-[10px] text-muted-foreground">{liveStepOlder.model}</span>
                                                        {/if}
                                                    </div>
                                                </div>
                                            {/if}
                                            {#key liveStepNewer.step_order}
                                                <div class="flex items-start gap-2 live-step-row" in:fade={{ duration: 280 }}>
                                                    <div class="flex items-center justify-center size-5 rounded-full bg-background border border-border shrink-0 mt-0.5">
                                                        <i class="fa-solid {liveStepIcon(liveStepNewer.step_type)} text-[8px]"></i>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="text-xs text-mono leading-snug">{liveStepNewer.title}</div>
                                                        {#if liveStepNewer.tool_name}
                                                            <span class="text-[10px] font-mono text-amber-600">{liveStepNewer.tool_name}</span>
                                                        {/if}
                                                        {#if liveStepNewer.model}
                                                            <span class="text-[10px] text-muted-foreground">{liveStepNewer.model}</span>
                                                        {/if}
                                                    </div>
                                                </div>
                                            {/key}
                                        </div>
                                    {/if}
                                </div>
                            </div>
                        {/if}
                    </div>
                {/if}
            </div>

            <div class="px-5 py-4 border-t border-border shrink-0">
                <div class="max-w-3xl mx-auto flex gap-2 items-end">
                    <input
                        type="file"
                        accept=".csv,.pdf"
                        class="hidden"
                        bind:this={fileInputEl}
                        on:change={handleFileSelected}
                    />
                    <button
                        type="button"
                        class="kt-btn kt-btn-icon kt-btn-sm kt-btn-outline shrink-0 mb-0.5"
                        on:click={triggerFileUpload}
                        disabled={sending || uploadingFile || isNewConversation || !selectedChat}
                        title="Attach CSV or PDF"
                    >
                        {#if uploadingFile}
                            <i class="fa-solid fa-spinner fa-spin text-sm"></i>
                        {:else}
                            <i class="fa-solid fa-paperclip text-sm"></i>
                        {/if}
                    </button>
                    <button
                        type="button"
                        class="kt-btn kt-btn-icon kt-btn-sm shrink-0 mb-0.5 {isRecording || isVoiceStarting ? 'kt-btn-destructive animate-pulse' : 'kt-btn-outline'}"
                        on:click={toggleVoiceInput}
                        disabled={sending}
                        title={isRecording ? 'Stop recording' : isVoiceStarting ? 'Starting microphone…' : 'Voice to message'}
                    >
                        <i class="fa-solid fa-microphone text-sm"></i>
                    </button>
                    <textarea
                        class="kt-textarea resize-y flex-1"
                        rows="1"
                        placeholder="Message the supervisor agent..."
                        bind:value={messageInput}
                        on:keydown={handleKeydown}
                        disabled={sending}
                    ></textarea>
                    <button
                        type="button"
                        class="kt-btn kt-btn-primary shrink-0"
                        on:click={sendMessage}
                        disabled={sending || !messageInput.trim()}
                    >
                        {#if sending}
                            <i class="fa-solid fa-spinner fa-spin"></i>
                        {:else}
                            <i class="fa-solid fa-paper-plane"></i>
                        {/if}
                    </button>
                </div>
            </div>
        </section>
    </div>

    <button style="display:none" data-kt-drawer-toggle="#reasoning_view_drawer" aria-label="Toggle reasoning drawer"></button>
    <ReasoningViewDrawer selectedMessage={selectedReasoningMessage} selectedChat={selectedChat} />
</MainLayout>

<style>
    .chats-card {
        height: calc(100vh - 10rem);
        min-height: 0;
    }

    .chats-sidebar {
        max-height: 38vh;
        flex-shrink: 0;
    }

    .chats-panel {
        flex: 1 1 auto;
        min-height: 0;
    }

    .chats-sidebar-scroll,
    .chats-messages {
        -webkit-overflow-scrolling: touch;
        touch-action: pan-y;
    }

    @media (min-width: 1024px) {
        .chats-sidebar {
            max-height: none;
        }
    }

    .live-steps-window {
        overflow: hidden;
        position: relative;
    }

    .live-step-row {
        transition: opacity 0.25s ease;
    }

    .live-step-row--older {
        opacity: 0.4;
    }
</style>
