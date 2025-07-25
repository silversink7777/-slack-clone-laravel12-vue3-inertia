<script setup>
import { ref, watch } from 'vue';
import { XMarkIcon, CheckIcon } from '@heroicons/vue/24/outline';
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
    show: Boolean,
    message: Object,
    activeDirectMessage: Object,
});

const emit = defineEmits(['close', 'messageUpdated']);

const editContent = ref('');
const isUpdating = ref(false);
const errorMessage = ref('');

// メッセージが変更されたときに編集内容を更新
watch(() => props.message, (newMessage) => {
    if (newMessage) {
        editContent.value = newMessage.content;
        errorMessage.value = '';
    }
}, { immediate: true });

const handleUpdate = async () => {
    if (!editContent.value.trim() || !props.message) {
        return;
    }

    isUpdating.value = true;
    errorMessage.value = '';

    try {
        let response;
        if (props.activeDirectMessage) {
            // DMメッセージの場合
            response = await axios.put(`/api/direct-messages/${props.message.id}`, {
                content: editContent.value.trim(),
            });
        } else {
            // チャンネルメッセージの場合
            response = await axios.put(`/messages/${props.message.id}`, {
                content: editContent.value.trim(),
            });
        }

        emit('messageUpdated', response.data);
        emit('close');
    } catch (error) {
        console.error('Message update failed:', error);
        if (error.response?.data?.message) {
            errorMessage.value = error.response.data.message;
        } else {
            errorMessage.value = 'メッセージの更新に失敗しました';
        }
    } finally {
        isUpdating.value = false;
    }
};

const handleClose = () => {
    editContent.value = props.message?.content || '';
    errorMessage.value = '';
    emit('close');
};

const handleKeyPress = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        handleUpdate();
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">メッセージを編集</h3>
                <button
                    @click="handleClose"
                    class="text-gray-500 hover:text-gray-700"
                >
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <div class="mb-4">
                <textarea
                    v-model="editContent"
                    @keypress="handleKeyPress"
                    class="w-full border border-gray-300 rounded-lg p-3 focus:ring-blue-500 focus:border-blue-500 resize-none"
                    placeholder="メッセージを入力してください"
                    rows="4"
                    :disabled="isUpdating"
                ></textarea>
                <div v-if="editContent" class="mt-2 p-2 bg-gray-50 rounded text-gray-800 border prose prose-sm max-w-none dark:prose-invert">
                    <span v-html="md.render(editContent)" class="markdown-content"></span>
                </div>
                <div v-if="errorMessage" class="mt-2 text-red-600 text-sm">
                    {{ errorMessage }}
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <button
                    @click="handleClose"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800"
                    :disabled="isUpdating"
                >
                    キャンセル
                </button>
                <button
                    @click="handleUpdate"
                    :disabled="!editContent.trim() || isUpdating"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center space-x-2"
                >
                    <CheckIcon class="h-4 w-4" />
                    <span>{{ isUpdating ? '更新中...' : '更新' }}</span>
                </button>
            </div>
        </div>
    </div>
</template> 