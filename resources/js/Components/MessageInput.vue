<script setup>
import { ref } from 'vue';
import {
    PlusIcon,
    FaceSmileIcon,
    AtSymbolIcon,
    VideoCameraIcon,
    MicrophoneIcon,
    PaperAirplaneIcon,
    EyeIcon,
    EyeSlashIcon,
    CodeBracketIcon,
    ListBulletIcon,
    ChatBubbleLeftRightIcon,
    BoldIcon,
    ItalicIcon,
    MinusIcon,
    LinkIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';
import MarkdownIt from 'markdown-it';
import hljs from 'highlight.js';
import 'highlight.js/styles/github-dark.css';

const md = new MarkdownIt({ 
    html: false, 
    linkify: true, 
    breaks: true,
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(str, { language: lang }).value;
            } catch (__) {}
        }
        return ''; // デフォルトのエスケープ処理を使用
    }
});

// リンクにtarget="_blank"とrel="noopener noreferrer"を追加
const defaultRender = md.renderer.rules.link_open || function(tokens, idx, options, env, self) {
    return self.renderToken(tokens, idx, options);
};

md.renderer.rules.link_open = function (tokens, idx, options, env, self) {
    const token = tokens[idx];
    token.attrSet('target', '_blank');
    token.attrSet('rel', 'noopener noreferrer');
    return defaultRender(tokens, idx, options, env, self);
};

const props = defineProps({
    activeChannel: Object,
    activeDirectMessage: Object,
});

const emit = defineEmits(['messageSent']);

const messageContent = ref('');
const isSending = ref(false);
const showPreview = ref(false);

// 絵文字ピッカー表示制御
const showEmojiPicker = ref(false);
const showCodeBlockMenu = ref(false);
const showLinkModal = ref(false);
const linkText = ref('');
const linkUrl = ref('');
const selectedTextForLink = ref('');
const emojiList = [
    '😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇',
    '🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚',
    '😋','😜','😝','😛','🤑','🤗','🤩','🤔','🤨','😐',
    '😑','😶','🙄','😏','😣','😥','😮','🤐','😯','😪',
    '😫','🥱','😴','😌','😛','😜','😝','🤤','😒','😓',
    '😔','😕','🙃','🤑','😲','☹️','🙁','😖','😞','😟',
    '😤','😢','😭','😦','😧','😨','😩','🤯','😬','😰',
    '😱','🥵','🥶','😳','🤪','😵','😡','😠','🤬','😷',
    '🤒','🤕','🤢','🤮','🥴','😇','🥳','🥺','🤠','😎',
    '🤓','🧐','😕','🤑','🤡','🤥','🤫','🤭','🫢','🫣',
    '🫡','🤗','🤔','🤭','🤫','🤥','🫠','🫨','🫤','🫦',
    '👍','👎','👏','🙌','🙏','💪','👀','👋','🤙','💯',
    '🔥','✨','🎉','🎊','❤️','🧡','💛','💚','💙','💜',
    '🖤','🤍','🤎','💔','💕','💞','💓','💗','💖','💘',
];

// コードブロック言語リスト
const codeLanguages = [
    { name: 'JavaScript', value: 'javascript' },
    { name: 'Python', value: 'python' },
    { name: 'PHP', value: 'php' },
    { name: 'HTML', value: 'html' },
    { name: 'CSS', value: 'css' },
    { name: 'SQL', value: 'sql' },
    { name: 'JSON', value: 'json' },
    { name: 'XML', value: 'xml' },
    { name: 'Markdown', value: 'markdown' },
    { name: 'Bash', value: 'bash' },
    { name: 'Java', value: 'java' },
    { name: 'C++', value: 'cpp' },
    { name: 'C#', value: 'csharp' },
    { name: 'Ruby', value: 'ruby' },
    { name: 'Go', value: 'go' },
    { name: 'Rust', value: 'rust' },
    { name: 'TypeScript', value: 'typescript' },
    { name: 'Vue', value: 'vue' },
    { name: 'React', value: 'jsx' },
    { name: 'なし', value: '' }
];

const file = ref(null);
const filePreview = ref(null);

const handleFileChange = (e) => {
    const f = e.target.files[0];
    file.value = f || null;
    if (f && f.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (ev) => {
            filePreview.value = ev.target.result;
        };
        reader.readAsDataURL(f);
    } else {
        filePreview.value = null;
    }
};

const clearFile = () => {
    file.value = null;
    filePreview.value = null;
    // input要素の値もクリア
    const input = document.getElementById('message-file-input');
    if (input) input.value = '';
};

const sendMessage = async () => {
    if ((!messageContent.value.trim() && !file) || (!props.activeChannel && !props.activeDirectMessage)) {
        return;
    }
    isSending.value = true;
    try {
        const formData = new FormData();
        formData.append('content', messageContent.value.trim());
        if (props.activeChannel) {
            formData.append('channel_id', props.activeChannel.id);
        }
        if (props.activeDirectMessage) {
            formData.append('receiver_id', props.activeDirectMessage.id);
        }
        if (file.value) {
            formData.append('file', file.value);
        }
        
        // CSRFトークンをFormDataに追加（axiosのインターセプターでもX-CSRF-TOKENヘッダーが設定される）
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
        console.log('CSRF Token:', csrfToken); // デバッグ用
        if (csrfToken) {
            formData.append('_token', csrfToken);
        }
        
        // FormDataの内容をデバッグ用に表示
        for (let [key, value] of formData.entries()) {
            console.log(`${key}:`, value);
        }
        
        let response;
        if (props.activeDirectMessage) {
            response = await axios.post('/api/direct-messages', formData);
        } else {
            response = await axios.post('/messages', formData);
        }
        emit('messageSent', response.data);
        messageContent.value = '';
        clearFile();
    } catch (error) {
        console.error('Message sending failed:', error);
        console.error('Error response:', error.response?.data); // デバッグ用
        console.error('Error status:', error.response?.status); // デバッグ用
        // TODO: エラーメッセージをユーザーに表示
    } finally {
        isSending.value = false;
    }
};

const handleKeyPress = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
};

// 絵文字をテキストエリアに挿入
const insertEmoji = (emoji) => {
    const el = document.activeElement;
    if (el && el.selectionStart !== undefined) {
        const start = el.selectionStart;
        const end = el.selectionEnd;
        messageContent.value =
            messageContent.value.substring(0, start) +
            emoji +
            messageContent.value.substring(end);
        // カーソル位置を絵文字の後ろに
        setTimeout(() => {
            el.selectionStart = el.selectionEnd = start + emoji.length;
            el.focus();
        }, 0);
    } else {
        messageContent.value += emoji;
    }
    showEmojiPicker.value = false;
};

// 選択されたテキストをコードブロック形式に変換
const convertToCodeBlock = () => {
    const textarea = document.querySelector('textarea');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = messageContent.value.substring(start, end);
    
    if (selectedText.trim()) {
        // 選択されたテキストをコードブロックで囲む
        const codeBlock = '```\n' + selectedText + '\n```';
        messageContent.value =
            messageContent.value.substring(0, start) +
            codeBlock +
            messageContent.value.substring(end);
        
        // カーソル位置をコードブロックの後ろに
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + codeBlock.length;
            textarea.focus();
        }, 0);
    } else {
        // テキストが選択されていない場合は、カーソル位置にコードブロックテンプレートを挿入
        const codeBlockTemplate = '```\n\n```';
        messageContent.value =
            messageContent.value.substring(0, start) +
            codeBlockTemplate +
            messageContent.value.substring(end);
        
        // カーソル位置をコードブロックの中に
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + 4; // ```\n の後ろ
            textarea.focus();
        }, 0);
    }
};

// 言語指定付きコードブロック変換
const convertToLanguageCodeBlock = (language = '') => {
    const textarea = document.querySelector('textarea');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = messageContent.value.substring(start, end);
    
    if (selectedText.trim()) {
        // 選択されたテキストを言語指定付きコードブロックで囲む
        const codeBlock = '```' + language + '\n' + selectedText + '\n```';
        messageContent.value =
            messageContent.value.substring(0, start) +
            codeBlock +
            messageContent.value.substring(end);
        
        // カーソル位置をコードブロックの後ろに
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + codeBlock.length;
            textarea.focus();
        }, 0);
    } else {
        // テキストが選択されていない場合は、カーソル位置に言語指定付きコードブロックテンプレートを挿入
        const codeBlockTemplate = '```' + language + '\n\n```';
        messageContent.value =
            messageContent.value.substring(0, start) +
            codeBlockTemplate +
            messageContent.value.substring(end);
        
        // カーソル位置をコードブロックの中に
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + 4 + language.length; // ```language\n の後ろ
            textarea.focus();
        }, 0);
    }
};

// 選択されたテキストを箇条書きリスト形式に変換
const convertToBulletList = () => {
    const textarea = document.querySelector('textarea');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = messageContent.value.substring(start, end);
    
    if (selectedText.trim()) {
        // 選択されたテキストを行ごとに分割して箇条書きリストに変換
        const lines = selectedText.split('\n');
        const bulletList = lines
            .map(line => line.trim())
            .filter(line => line.length > 0)
            .map(line => '- ' + line)
            .join('\n');
        
        messageContent.value =
            messageContent.value.substring(0, start) +
            bulletList +
            messageContent.value.substring(end);
        
        // カーソル位置を箇条書きリストの後ろに
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + bulletList.length;
            textarea.focus();
        }, 0);
    } else {
        // テキストが選択されていない場合は、カーソル位置に箇条書きリストテンプレートを挿入
        const bulletListTemplate = '- ';
        messageContent.value =
            messageContent.value.substring(0, start) +
            bulletListTemplate +
            messageContent.value.substring(end);
        
        // カーソル位置を箇条書きリストの後ろに
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + bulletListTemplate.length;
            textarea.focus();
        }, 0);
    }
};

// 選択されたテキストを引用形式に変換
const convertToQuote = () => {
    const textarea = document.querySelector('textarea');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = messageContent.value.substring(start, end);
    
    if (selectedText.trim()) {
        // 選択されたテキストを行ごとに分割して引用形式に変換
        const lines = selectedText.split('\n');
        const quoteText = lines
            .map(line => line.trim())
            .filter(line => line.length > 0)
            .map(line => '> ' + line)
            .join('\n');
        
        messageContent.value =
            messageContent.value.substring(0, start) +
            quoteText +
            messageContent.value.substring(end);
        
        // カーソル位置を引用テキストの後ろに
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + quoteText.length;
            textarea.focus();
        }, 0);
    } else {
        // テキストが選択されていない場合は、カーソル位置に引用テンプレートを挿入
        const quoteTemplate = '> ';
        messageContent.value =
            messageContent.value.substring(0, start) +
            quoteTemplate +
            messageContent.value.substring(end);
        
        // カーソル位置を引用テンプレートの後ろに
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + quoteTemplate.length;
            textarea.focus();
        }, 0);
    }
};

// 選択されたテキストを太文字形式に変換
const convertToBold = () => {
    const textarea = document.querySelector('textarea');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = messageContent.value.substring(start, end);
    
    if (selectedText.trim()) {
        // 選択されたテキストを太文字形式で囲む
        const boldText = '**' + selectedText + '**';
        messageContent.value =
            messageContent.value.substring(0, start) +
            boldText +
            messageContent.value.substring(end);
        
        // カーソル位置を太文字テキストの後ろに
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + boldText.length;
            textarea.focus();
        }, 0);
    } else {
        // テキストが選択されていない場合は、カーソル位置に太文字テンプレートを挿入
        const boldTemplate = '**太文字**';
        messageContent.value =
            messageContent.value.substring(0, start) +
            boldTemplate +
            messageContent.value.substring(end);
        
        // カーソル位置を太文字テンプレートの中に
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + 2; // ** の後ろ
            textarea.focus();
        }, 0);
    }
};

// 選択されたテキストを斜体形式に変換
const convertToItalic = () => {
    const textarea = document.querySelector('textarea');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = messageContent.value.substring(start, end);
    
    if (selectedText.trim()) {
        // 選択されたテキストを斜体形式で囲む
        const italicText = '*' + selectedText + '*';
        messageContent.value =
            messageContent.value.substring(0, start) +
            italicText +
            messageContent.value.substring(end);
        
        // カーソル位置を斜体テキストの後ろに
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + italicText.length;
            textarea.focus();
        }, 0);
    } else {
        // テキストが選択されていない場合は、カーソル位置に斜体テンプレートを挿入
        const italicTemplate = '*斜体*';
        messageContent.value =
            messageContent.value.substring(0, start) +
            italicTemplate +
            messageContent.value.substring(end);
        
        // カーソル位置を斜体テンプレートの中に
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + 1; // * の後ろ
            textarea.focus();
        }, 0);
    }
};

// 選択されたテキストを取り消し線形式に変換
const convertToStrikethrough = () => {
    const textarea = document.querySelector('textarea');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = messageContent.value.substring(start, end);
    
    if (selectedText.trim()) {
        // 選択されたテキストを取り消し線形式で囲む
        const strikethroughText = '~~' + selectedText + '~~';
        messageContent.value =
            messageContent.value.substring(0, start) +
            strikethroughText +
            messageContent.value.substring(end);
        
        // カーソル位置を取り消し線テキストの後ろに
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + strikethroughText.length;
            textarea.focus();
        }, 0);
    } else {
        // テキストが選択されていない場合は、カーソル位置に取り消し線テンプレートを挿入
        const strikethroughTemplate = '~~取り消し線~~';
        messageContent.value =
            messageContent.value.substring(0, start) +
            strikethroughTemplate +
            messageContent.value.substring(end);
        
        // カーソル位置を取り消し線テンプレートの中に
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + 2; // ~~ の後ろ
            textarea.focus();
        }, 0);
    }
};

// 選択されたテキストを順番付きリスト形式に変換
const convertToNumberedList = () => {
    const textarea = document.querySelector('textarea');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = messageContent.value.substring(start, end);
    
    if (selectedText.trim()) {
        // 選択されたテキストを行ごとに分割して順番付きリストに変換
        const lines = selectedText.split('\n');
        const numberedList = lines
            .map(line => line.trim())
            .filter(line => line.length > 0)
            .map((line, index) => `${index + 1}. ${line}`)
            .join('\n');
        
        messageContent.value =
            messageContent.value.substring(0, start) +
            numberedList +
            messageContent.value.substring(end);
        
        // カーソル位置を順番付きリストの後ろに
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + numberedList.length;
            textarea.focus();
        }, 0);
    } else {
        // テキストが選択されていない場合は、カーソル位置に順番付きリストテンプレートを挿入
        const numberedListTemplate = '1. ';
        messageContent.value =
            messageContent.value.substring(0, start) +
            numberedListTemplate +
            messageContent.value.substring(end);
        
        // カーソル位置を順番付きリストテンプレートの後ろに
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + numberedListTemplate.length;
            textarea.focus();
        }, 0);
    }
};

// リンクモーダルを開く
const openLinkModal = (event) => {
    if (event) {
        event.stopPropagation(); // イベントの伝播を停止
    }
    console.log('openLinkModal called'); // デバッグログ
    const textarea = document.querySelector('textarea');
    if (!textarea) {
        console.log('textarea not found'); // デバッグログ
        return;
    }
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = messageContent.value.substring(start, end);
    
    console.log('Selected text:', selectedText); // デバッグログ
    console.log('showLinkModal before:', showLinkModal.value); // デバッグログ
    
    selectedTextForLink.value = selectedText;
    linkText.value = selectedText.trim();
    linkUrl.value = '';
    showLinkModal.value = true;
    
    console.log('showLinkModal after:', showLinkModal.value); // デバッグログ
};

// リンクを適用
const applyLink = () => {
    const textarea = document.querySelector('textarea');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    
    if (!linkText.value.trim() || !linkUrl.value.trim()) {
        return;
    }
    
    const linkMarkdown = `[${linkText.value}](${linkUrl.value})`;
    
    if (selectedTextForLink.value.trim()) {
        // 選択されたテキストをリンクに置き換え
        messageContent.value =
            messageContent.value.substring(0, start) +
            linkMarkdown +
            messageContent.value.substring(end);
        
        // カーソル位置をリンクの後ろに
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + linkMarkdown.length;
            textarea.focus();
        }, 0);
    } else {
        // テキストが選択されていない場合は、カーソル位置にリンクを挿入
        messageContent.value =
            messageContent.value.substring(0, start) +
            linkMarkdown +
            messageContent.value.substring(end);
        
        // カーソル位置をリンクの後ろに
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + linkMarkdown.length;
            textarea.focus();
        }, 0);
    }
    
    // モーダルを閉じる
    closeLinkModal();
};

// リンクモーダルを閉じる
const closeLinkModal = () => {
    console.log('closeLinkModal called'); // デバッグログ
    console.log('showLinkModal before close:', showLinkModal.value); // デバッグログ
    showLinkModal.value = false;
    linkText.value = '';
    linkUrl.value = '';
    selectedTextForLink.value = '';
    console.log('showLinkModal after close:', showLinkModal.value); // デバッグログ
};

// ピッカー外クリックで閉じる
const handleClickOutside = (event) => {
    if (
        showEmojiPicker.value &&
        !event.target.closest('.emoji-picker-popover') &&
        !event.target.closest('.emoji-picker-toggle')
    ) {
        showEmojiPicker.value = false;
    }
    
    if (
        showCodeBlockMenu.value &&
        !event.target.closest('.code-block-menu-popover') &&
        !event.target.closest('.code-block-menu-toggle')
    ) {
        showCodeBlockMenu.value = false;
    }
    
    if (
        showLinkModal.value &&
        !event.target.closest('.link-modal-popover') &&
        !event.target.closest('.link-button-toggle')
    ) {
        console.log('Closing link modal due to outside click'); // デバッグログ
        closeLinkModal();
    }
};

if (typeof window !== 'undefined') {
    window.addEventListener('click', handleClickOutside);
}
</script>

<template>
    <div class="p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-2 flex flex-col">
            <div class="relative">
                <textarea
                    v-model="messageContent"
                    @keypress="handleKeyPress"
                    class="w-full border-none focus:ring-0 resize-none bg-transparent text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                    placeholder="メモを書く"
                    rows="1"
                    :disabled="isSending"
                ></textarea>
                
                <!-- Markdownプレビュー -->
                <div v-if="showPreview && messageContent.trim()" class="mt-2 p-3 bg-gray-50 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">プレビュー:</div>
                    <div class="text-sm text-gray-800 dark:text-gray-200 prose prose-sm max-w-none dark:prose-invert markdown-content" v-html="md.render(messageContent)"></div>
                </div>
                
                <!-- ファイルプレビュー -->
                <div v-if="filePreview" class="mt-2">
                    <img :src="filePreview" alt="preview" class="max-h-32 rounded border" />
                </div>
                <div v-else-if="file && file.name" class="mt-2">
                    <span class="text-xs text-gray-500">{{ file.name }}</span>
                </div>
                <button v-if="file" @click="clearFile" class="text-xs text-red-500 ml-2">ファイルを削除</button>
                
                <!-- シンプルな絵文字ピッカー -->
                <div v-if="showEmojiPicker" class="emoji-picker-popover absolute left-0 bottom-12 z-50 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded shadow-lg p-2 w-80 max-h-60 overflow-y-auto flex flex-wrap gap-1">
                    <button
                        v-for="emoji in emojiList"
                        :key="emoji"
                        class="text-2xl p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                        @click.stop="insertEmoji(emoji)"
                        type="button"
                    >
                        {{ emoji }}
                    </button>
                </div>
                
                <!-- コードブロック言語選択メニュー -->
                <div v-if="showCodeBlockMenu" class="code-block-menu-popover absolute left-0 bottom-12 z-50 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded shadow-lg p-2 w-48 max-h-60 overflow-y-auto">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 px-2">言語を選択:</div>
                    <button
                        v-for="lang in codeLanguages"
                        :key="lang.value"
                        class="w-full text-left px-2 py-1 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                        @click.stop="convertToLanguageCodeBlock(lang.value); showCodeBlockMenu = false"
                        type="button"
                    >
                        {{ lang.name }}
                    </button>
                </div>
            </div>
            <div class="flex items-center justify-between mt-2">
                <div class="flex items-center space-x-2">
                    <label class="cursor-pointer text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300">
                        <PlusIcon class="h-6 w-6" />
                        <input id="message-file-input" type="file" class="hidden" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" @change="handleFileChange" />
                    </label>
                    <button class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"><VideoCameraIcon class="h-6 w-6" /></button>
                    <button class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"><MicrophoneIcon class="h-6 w-6" /></button>
                    <!-- コードブロックボタン -->
                    <div class="relative">
                        <button 
                            @click="convertToCodeBlock"
                            @contextmenu.prevent="showCodeBlockMenu = !showCodeBlockMenu"
                            class="code-block-menu-toggle text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"
                            title="コードブロックに変換 (右クリックで言語選択)"
                            type="button"
                        >
                            <CodeBracketIcon class="h-6 w-6" />
                        </button>
                    </div>
                    <!-- 箇条書きリストボタン -->
                    <button 
                        @click="convertToBulletList"
                        class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"
                        title="箇条書きリストに変換"
                        type="button"
                    >
                        <ListBulletIcon class="h-6 w-6" />
                    </button>
                    <!-- 順番付きリストボタン -->
                    <button 
                        @click="convertToNumberedList"
                        class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"
                        title="順番付きリストに変換"
                        type="button"
                    >
                        <ListBulletIcon class="h-6 w-6 transform rotate-90" />
                    </button>
                    <!-- 引用ボタン -->
                    <button 
                        @click="convertToQuote"
                        class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"
                        title="引用に変換"
                        type="button"
                    >
                        <ChatBubbleLeftRightIcon class="h-6 w-6" />
                    </button>
                    <!-- 太文字ボタン -->
                    <button 
                        @click="convertToBold"
                        class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"
                        title="太文字に変換"
                        type="button"
                    >
                        <BoldIcon class="h-6 w-6" />
                    </button>
                    <!-- 斜体ボタン -->
                    <button 
                        @click="convertToItalic"
                        class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"
                        title="斜体に変換"
                        type="button"
                    >
                        <ItalicIcon class="h-6 w-6" />
                    </button>
                    <!-- 取り消し線ボタン -->
                    <button 
                        @click="convertToStrikethrough"
                        class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"
                        title="取り消し線に変換"
                        type="button"
                    >
                        <MinusIcon class="h-6 w-6" />
                    </button>
                    <!-- リンクボタン -->
                    <button 
                        @click.stop="(event) => { console.log('Link button clicked'); openLinkModal(event); }"
                        class="link-button-toggle text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"
                        title="リンクを追加"
                        type="button"
                    >
                        <LinkIcon class="h-6 w-6" />
                    </button>
                </div>
                <div class="flex items-center space-x-2">
                    <!-- プレビュートグルボタン -->
                    <button 
                        v-if="messageContent.trim()"
                        @click="showPreview = !showPreview"
                        class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"
                        :title="showPreview ? 'プレビューを非表示' : 'プレビューを表示'"
                        type="button"
                    >
                        <EyeIcon v-if="!showPreview" class="h-6 w-6" />
                        <EyeSlashIcon v-else class="h-6 w-6" />
                    </button>
                    <button 
                        class="emoji-picker-toggle text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"
                        @click.stop="showEmojiPicker = !showEmojiPicker"
                        title="絵文字を挿入"
                        type="button"
                    >
                        <FaceSmileIcon class="h-6 w-6" />
                    </button>
                    <button class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300"><AtSymbolIcon class="h-6 w-6" /></button>
                    <button 
                        @click="sendMessage"
                        :disabled="(!messageContent.trim() && !file) || isSending"
                        class="bg-gray-800 text-white rounded p-1 hover:bg-gray-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
                        type="button"
                    >
                        <PaperAirplaneIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
        
        <!-- リンクモーダル -->
        <div v-if="showLinkModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-30">
            <!-- Debug info -->
            <div class="absolute top-0 left-0 bg-red-500 text-white p-2 text-xs">
                Modal is visible - showLinkModal: {{ showLinkModal }}
            </div>
            <div class="link-modal-popover bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">リンクを追加する</h3>
                    <button
                        @click="closeLinkModal"
                        class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            テキスト
                        </label>
                        <input
                            v-model="linkText"
                            type="text"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="リンクテキストを入力"
                        />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            リンク
                        </label>
                        <input
                            v-model="linkUrl"
                            type="url"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="https://example.com"
                        />
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button
                        @click="closeLinkModal"
                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                    >
                        キャンセル
                    </button>
                    <button
                        @click="applyLink"
                        :disabled="!linkText.trim() || !linkUrl.trim()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
                    >
                        保存する
                    </button>
                </div>
            </div>
        </div>
    </div>
</template> 