import {
    analyze,
    expand,
    generate,
    rewrite,
    suggest,
} from '@/actions/App/Http/Controllers/ChapterController';
import { Chapter, Novel } from '@/types/novel';
import axios from 'axios';
import { useState } from 'react';

interface UseNovelAIProps {
    novel: Novel;
    activeChapter: Chapter | null;
    onUpdateContent: (content: string) => void;
    onError: (error: unknown) => void;
}

export function useNovelAI({
    novel,
    activeChapter,
    onUpdateContent,
    onError,
}: UseNovelAIProps) {
    const [generating, setGenerating] = useState(false);
    const [isBusy, setIsBusy] = useState(false);
    const [analysisOpen, setAnalysisOpen] = useState(false);
    const [analysisResult, setAnalysisResult] = useState('');

    const handleGenerate = async (prompt: string, webSearchEnabled: boolean) => {
        if (!activeChapter) return;
        setGenerating(true);
        try {
            const response = await axios.post(
                generate.url({ novel: novel.id, chapter: activeChapter.id }),
                {
                    prompt: prompt,
                    mode: webSearchEnabled ? 'web' : 'context',
                },
            );
            onUpdateContent(response.data.text);
        } catch (error) {
            onError(error);
        } finally {
            setGenerating(false);
        }
    };

    const handleAnalyze = async (text: string) => {
        if (!activeChapter) return;
        setIsBusy(true);
        try {
            const response = await axios.post(
                analyze.url({ novel: novel.id, chapter: activeChapter.id }),
                {
                    selection: text,
                },
            );
            setAnalysisResult(response.data.analysis);
            setAnalysisOpen(true);
        } catch (error) {
            onError(error);
        } finally {
            setIsBusy(false);
        }
    };

    const handleSuggest = async (text: string) => {
        if (!activeChapter) return;
        setIsBusy(true);
        try {
            const response = await axios.post(
                suggest.url({ novel: novel.id, chapter: activeChapter.id }),
                {
                    context: text,
                },
            );
            onUpdateContent(' ' + response.data.suggestion);
        } catch (error) {
            onError(error);
        } finally {
            setIsBusy(false);
        }
    };

    const handleRewrite = async (selection: string, instruction: string) => {
        if (!activeChapter) return;
        setIsBusy(true);
        try {
            const response = await axios.post(
                rewrite.url({ novel: novel.id, chapter: activeChapter.id }),
                {
                    selection: selection,
                    instruction: instruction,
                },
            );
            setAnalysisResult('Rewritten Text:\n\n' + response.data.rewritten);
            setAnalysisOpen(true);
        } catch (error) {
            onError(error);
        } finally {
            setIsBusy(false);
        }
    };

    const handleExpand = async (selection: string) => {
        if (!activeChapter) return;
        setIsBusy(true);
        try {
            const response = await axios.post(
                expand.url({ novel: novel.id, chapter: activeChapter.id }),
                {
                    selection: selection,
                },
            );
            setAnalysisResult('Expanded Scene:\n\n' + response.data.expanded);
            setAnalysisOpen(true);
        } catch (error) {
            onError(error);
        } finally {
            setIsBusy(false);
        }
    };

    return {
        generating,
        isBusy,
        analysisOpen,
        setAnalysisOpen,
        analysisResult,
        handleGenerate,
        handleAnalyze,
        handleSuggest,
        handleRewrite,
        handleExpand,
    };
}
