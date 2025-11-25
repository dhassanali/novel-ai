import { Novel } from '@/types/novel';
import axios from 'axios';
import { useState } from 'react';

interface UseBrainstormProps {
    novel: Novel;
    onError: (error: unknown) => void;
}

export function useBrainstorm({ novel, onError }: UseBrainstormProps) {
    const [generating, setGenerating] = useState(false);
    const [suggestions, setSuggestions] = useState<string[]>([]);
    const [keepers, setKeepers] = useState<string[]>([]);

    const generateIdeas = async (category: string, context: string) => {
        setGenerating(true);
        try {
            const response = await axios.post(`/novels/${novel.id}/brainstorm`, {
                category,
                context,
            });
            setSuggestions(response.data.suggestions);
        } catch (error) {
            onError(error);
        } finally {
            setGenerating(false);
        }
    };

    const addKeeper = (idea: string) => {
        if (!keepers.includes(idea)) {
            setKeepers([...keepers, idea]);
        }
    };

    const removeKeeper = (idea: string) => {
        setKeepers(keepers.filter((k) => k !== idea));
    };

    const removeSuggestion = (idea: string) => {
        setSuggestions(suggestions.filter((s) => s !== idea));
    };

    const clearKeepers = () => {
        setKeepers([]);
    };

    return {
        generating,
        suggestions,
        keepers,
        generateIdeas,
        addKeeper,
        removeKeeper,
        removeSuggestion,
        clearKeepers,
    };
}
