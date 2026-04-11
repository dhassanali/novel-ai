export type ChapterStatus = 'draft' | 'writing' | 'complete';

export type RelationshipType = 'friend' | 'enemy' | 'lover' | 'family' | 'rival' | 'mentor' | 'ally';

export interface Chapter {
    id: number;
    title: string;
    content: string;
    order: number;
    word_count: number;
    status: ChapterStatus;
    pov_character_id: number | null;
}

export interface SourceDocument {
    id: number;
    filename: string;
    status: string;
    type: string;
}

export interface CharacterRelationship {
    id: number;
    related_character_id: number;
    type: RelationshipType;
    description: string | null;
    related_character: {
        id: number;
        name: string;
    };
}

export interface Character {
    id: number;
    name: string;
    description: string;
    role: string;
    personality_traits: string | null;
    backstory: string | null;
    goals: string | null;
    speech_patterns: string | null;
    relationships: CharacterRelationship[];
}

export interface Location {
    id: number;
    name: string;
    description: string;
}

export interface Novel {
    id: number;
    title: string;
    description: string;
    genre: string;
    total_word_count: number;
    word_count_goal: number | null;
    cover_image_url: string | null;
    chapters: Chapter[];
    source_documents: SourceDocument[];
    characters: Character[];
    locations: Location[];
}

export interface ConsistencyIssue {
    severity: 'high' | 'medium' | 'low';
    category: 'character' | 'plot' | 'timeline' | 'world';
    description: string;
}

export interface ConsistencyReport {
    issues: ConsistencyIssue[];
    summary: string;
}

export interface ChatMessage {
    role: 'user' | 'character';
    content: string;
}
