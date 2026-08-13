import { useEffect, useState } from 'react';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import TextAlign from '@tiptap/extension-text-align';
import { Placeholder } from '@tiptap/extensions';
import {
  AlignCenter, AlignLeft, AlignRight, Bold, Heading2, Heading3, Image as IkonGambar,
  Italic, Link2, Link2Off, List, ListOrdered, Pilcrow, Quote, Redo2,
  Strikethrough, Underline as IkonGarisBawah, Undo2,
} from 'lucide-react';
import PemilihMedia from '@/Components/PemilihMedia';

/**
 * Penyunting teks kaya (tiptap) — port `components/shared/rich-editor.tsx`.
 * Dipakai berita dan blok konten CMS.
 *
 * Gambar disisipkan dari **pustaka media**, bukan diunggah ke dalam HTML
 * sebagai base64: badan berita yang memuat gambar base64 membengkak sampai
 * ratusan KB per baris dan tidak bisa di-cache peramban.
 */

function TombolAlat({ onClick, aktif, nonaktif, judul, children }) {
  return (
    <button type="button" onMouseDown={(e) => e.preventDefault()} onClick={onClick}
            disabled={nonaktif} title={judul}
            className={`flex h-8 w-8 items-center justify-center rounded-md text-slate-600 transition-colors hover:bg-slate-200 disabled:opacity-40 ${
              aktif ? 'bg-brand/10 text-brand' : ''
            }`}>
      {children}
    </button>
  );
}

function Pemisah() {
  return <span className="mx-0.5 h-5 w-px bg-slate-200" />;
}

function BilahAlat({ editor, onGalat }) {
  const [pemilih, setPemilih] = useState(false);

  const tautan = () => {
    const sebelum = editor.getAttributes('link').href;
    const url = window.prompt('Masukkan URL tautan', sebelum ?? 'https://');
    if (url === null) return;

    if (url === '') {
      editor.chain().focus().extendMarkRange('link').unsetLink().run();
      return;
    }
    editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
  };

  return (
    <div className="sticky top-0 z-10 flex flex-wrap items-center gap-0.5 rounded-t-lg border-b border-slate-200 bg-slate-50 p-1.5">
      <TombolAlat onClick={() => editor.chain().focus().toggleBold().run()} aktif={editor.isActive('bold')} judul="Tebal"><Bold className="h-4 w-4" /></TombolAlat>
      <TombolAlat onClick={() => editor.chain().focus().toggleItalic().run()} aktif={editor.isActive('italic')} judul="Miring"><Italic className="h-4 w-4" /></TombolAlat>
      <TombolAlat onClick={() => editor.chain().focus().toggleUnderline().run()} aktif={editor.isActive('underline')} judul="Garis bawah"><IkonGarisBawah className="h-4 w-4" /></TombolAlat>
      <TombolAlat onClick={() => editor.chain().focus().toggleStrike().run()} aktif={editor.isActive('strike')} judul="Coret"><Strikethrough className="h-4 w-4" /></TombolAlat>

      <Pemisah />

      <TombolAlat onClick={() => editor.chain().focus().setParagraph().run()} aktif={editor.isActive('paragraph')} judul="Paragraf"><Pilcrow className="h-4 w-4" /></TombolAlat>
      <TombolAlat onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()} aktif={editor.isActive('heading', { level: 2 })} judul="Judul"><Heading2 className="h-4 w-4" /></TombolAlat>
      <TombolAlat onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()} aktif={editor.isActive('heading', { level: 3 })} judul="Sub-judul"><Heading3 className="h-4 w-4" /></TombolAlat>

      <Pemisah />

      <TombolAlat onClick={() => editor.chain().focus().toggleBulletList().run()} aktif={editor.isActive('bulletList')} judul="Daftar poin"><List className="h-4 w-4" /></TombolAlat>
      <TombolAlat onClick={() => editor.chain().focus().toggleOrderedList().run()} aktif={editor.isActive('orderedList')} judul="Daftar bernomor"><ListOrdered className="h-4 w-4" /></TombolAlat>
      <TombolAlat onClick={() => editor.chain().focus().toggleBlockquote().run()} aktif={editor.isActive('blockquote')} judul="Kutipan"><Quote className="h-4 w-4" /></TombolAlat>

      <Pemisah />

      <TombolAlat onClick={() => editor.chain().focus().setTextAlign('left').run()} aktif={editor.isActive({ textAlign: 'left' })} judul="Rata kiri"><AlignLeft className="h-4 w-4" /></TombolAlat>
      <TombolAlat onClick={() => editor.chain().focus().setTextAlign('center').run()} aktif={editor.isActive({ textAlign: 'center' })} judul="Rata tengah"><AlignCenter className="h-4 w-4" /></TombolAlat>
      <TombolAlat onClick={() => editor.chain().focus().setTextAlign('right').run()} aktif={editor.isActive({ textAlign: 'right' })} judul="Rata kanan"><AlignRight className="h-4 w-4" /></TombolAlat>

      <Pemisah />

      <TombolAlat onClick={tautan} aktif={editor.isActive('link')} judul="Tautan"><Link2 className="h-4 w-4" /></TombolAlat>
      <TombolAlat onClick={() => editor.chain().focus().unsetLink().run()} nonaktif={!editor.isActive('link')} judul="Hapus tautan"><Link2Off className="h-4 w-4" /></TombolAlat>
      <TombolAlat onClick={() => setPemilih(true)} judul="Sisipkan gambar"><IkonGambar className="h-4 w-4" /></TombolAlat>

      <Pemisah />

      <TombolAlat onClick={() => editor.chain().focus().undo().run()} nonaktif={!editor.can().undo()} judul="Urungkan"><Undo2 className="h-4 w-4" /></TombolAlat>
      <TombolAlat onClick={() => editor.chain().focus().redo().run()} nonaktif={!editor.can().redo()} judul="Ulangi"><Redo2 className="h-4 w-4" /></TombolAlat>

      {pemilih && (
        <PemilihMedia judul="Sisipkan Gambar" onGalat={onGalat} onTutup={() => setPemilih(false)}
                      onPilih={(m) => editor.chain().focus().setImage({ src: m.url, alt: m.namaAsli }).run()} />
      )}
    </div>
  );
}

export default function PenyuntingKaya({ nilai, onUbah, placeholder, onGalat }) {
  const teks = placeholder ?? 'Tulis isi berita di sini...';

  const editor = useEditor({
    immediatelyRender: false,
    extensions: [
      StarterKit.configure({ heading: { levels: [2, 3] } }),
      Underline,
      Link.configure({ openOnClick: false, autolink: true, HTMLAttributes: { class: 'text-brand underline' } }),
      Image.configure({ HTMLAttributes: { class: 'rounded-lg my-2 max-w-full' } }),
      TextAlign.configure({ types: ['heading', 'paragraph'] }),
      Placeholder.configure({ placeholder: teks }),
    ],
    content: nilai || '',
    editorProps: {
      attributes: {
        class: 'prose prose-slate prose-sm max-w-none min-h-[220px] px-4 py-3 focus:outline-none',
        'data-placeholder': teks,
      },
    },
    onUpdate: ({ editor: e }) => onUbah(e.getHTML()),
  });

  // Menyelaraskan nilai dari luar (mis. saat form ubah dibuka) TANPA mengganggu
  // yang sedang mengetik — memasang ulang konten saat editor fokus akan
  // melompatkan kursor ke awal di tengah kalimat.
  useEffect(() => {
    if (!editor) return;
    if (nilai !== editor.getHTML() && !editor.isFocused) {
      editor.commands.setContent(nilai || '', { emitUpdate: false });
    }
  }, [nilai, editor]);

  return (
    <div className="rounded-lg border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-brand/40">
      {editor && <BilahAlat editor={editor} onGalat={onGalat} />}
      <EditorContent editor={editor} />
    </div>
  );
}
