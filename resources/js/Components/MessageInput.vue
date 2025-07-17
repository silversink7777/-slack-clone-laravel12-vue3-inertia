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
        // CSRFトークンをmetaから取得
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
        const headers = { 'Content-Type': 'multipart/form-data' };
        if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
        let response;
        if (props.activeDirectMessage) {
            response = await axios.post('/api/direct-messages', formData, { headers });
        } else {
            response = await axios.post('/messages', formData, { headers });
        }
        emit('messageSent', response.data);
        messageContent.value = '';
        clearFile();
    } catch (error) {
        console.error('Message sending failed:', error);
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
    </div>
</template> 