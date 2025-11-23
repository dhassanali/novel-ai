import { useEditor, EditorContent } from '@tiptap/react'
import { BubbleMenu, FloatingMenu } from '@tiptap/react/menus'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import BubbleMenuExtension from '@tiptap/extension-bubble-menu'
import FloatingMenuExtension from '@tiptap/extension-floating-menu'
import { Button } from '@/components/ui/button'
import { Bold, Italic, Sparkles, Wand2, Loader2 } from 'lucide-react'
import { useEffect } from 'react'

interface EditorProps {
    content: string
    onChange: (content: string) => void
    onAnalyze?: (text: string) => void
    onSuggest?: (text: string) => void
    isBusy?: boolean
}

export default function Editor({ content, onChange, onAnalyze, onSuggest, isBusy = false }: EditorProps) {
    const editor = useEditor({
        extensions: [
            StarterKit,
            Placeholder.configure({
                placeholder: 'Start writing your masterpiece...',
            }),
            BubbleMenuExtension,
            FloatingMenuExtension,
        ],
        content: content,
        editorProps: {
            attributes: {
                class: 'prose prose-lg dark:prose-invert max-w-none focus:outline-none min-h-[500px] p-4',
            },
        },
        onUpdate: ({ editor }) => {
            onChange(editor.getHTML())
        },
    })

    // Update editor content if content prop changes externally (e.g. switching chapters)
    useEffect(() => {
        if (editor && content !== editor.getHTML()) {
            editor.commands.setContent(content)
        }
    }, [content, editor])

    if (!editor) {
        return null
    }

    return (
        <div className="relative w-full">
            {editor && (
                <BubbleMenu className="flex gap-1 bg-background border rounded-lg shadow-lg p-1" editor={editor}>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => editor.chain().focus().toggleBold().run()}
                        className={editor.isActive('bold') ? 'bg-accent' : ''}
                        disabled={isBusy}
                    >
                        <Bold className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => editor.chain().focus().toggleItalic().run()}
                        className={editor.isActive('italic') ? 'bg-accent' : ''}
                        disabled={isBusy}
                    >
                        <Italic className="h-4 w-4" />
                    </Button>
                    <div className="w-px bg-border mx-1" />
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            const selection = editor.state.selection
                            const text = editor.state.doc.textBetween(selection.from, selection.to)
                            onAnalyze?.(text)
                        }}
                        disabled={isBusy}
                    >
                        {isBusy ? <Loader2 className="h-4 w-4 mr-1 animate-spin" /> : <Sparkles className="h-4 w-4 mr-1" />}
                        Analyze
                    </Button>
                </BubbleMenu>
            )}

            {editor && (
                <FloatingMenu className="flex gap-1 bg-background border rounded-lg shadow-lg p-1" editor={editor}>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            const pos = editor.state.selection.from
                            const textBefore = editor.state.doc.textBetween(Math.max(0, pos - 3000), pos)
                            onSuggest?.(textBefore)
                        }}
                        disabled={isBusy}
                    >
                        {isBusy ? <Loader2 className="h-4 w-4 mr-1 animate-spin" /> : <Wand2 className="h-4 w-4 mr-1" />}
                        Continue
                    </Button>
                </FloatingMenu>
            )}

            <EditorContent editor={editor} />
        </div>
    )
}
