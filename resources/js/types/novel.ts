export interface Chapter {
    id: number;
    title: string;
    content: string;
    order: number;
    word_count: number;
}

export interface SourceDocument {
    id: number;
    filename: string;
    status: string;
    type: string;
}

export interface Character {
    id: number;
    name: string;
    description: string;
    role: string;
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
    cover_image_url: string | null;
    chapters: Chapter[];
    source_documents: SourceDocument[];
    characters: Character[];
    locations: Location[];
}
