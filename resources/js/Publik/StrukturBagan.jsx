import { Tree, TreeNode } from 'react-organizational-chart';

/**
 * Bagan struktur organisasi — port `components/landingpage/struktur-chart.tsx`.
 *
 * 🔴 Dipisah dari `ProfilTabs` dan dimuat MALAS. `react-organizational-chart`
 * menyentuh `document` saat modulnya di-import, jadi ia hanya boleh dieksekusi
 * di peramban — sekaligus supaya pengunjung yang tidak pernah membuka tab
 * Struktur tidak ikut mengunduhnya.
 *
 * Bentuk bagannya ditentukan `parent` tiap baris; jabatan tanpa `parent` (atau
 * yang parent-nya tidak ada di daftar) menjadi akar. Sengaja begitu supaya
 * struktur yang setengah terisi dari dashboard tetap tergambar, bukan hilang.
 */

function Kotak({ node, akar = false }) {
  const adaNama = node.nama && node.nama !== '-';

  return (
    <div className={`w-[190px] shrink-0 rounded-xl border px-3.5 py-2.5 text-center ${
      akar
        ? 'border-transparent text-white shadow-md shadow-brand/25'
        : 'border-brand/15 bg-gradient-to-br from-brand/[0.09] to-brand/[0.03] shadow-sm'
    }`} style={akar ? { background: 'linear-gradient(135deg, #2176bd, #1b4b72)' } : undefined}>
      <p className={`text-xs font-semibold leading-tight ${akar ? 'text-white' : 'text-slate-900'}`}>
        {node.jabatan}
      </p>
      {adaNama && (
        <p className={`mt-0.5 text-[0.68rem] ${akar ? 'text-white/75' : 'text-slate-500'}`}>{node.nama}</p>
      )}
    </div>
  );
}

function Cabang({ nodes, anakDari }) {
  return nodes.map((n) => (
    <TreeNode key={n.jabatan} label={<div className="inline-flex"><Kotak node={n} /></div>}>
      <Cabang nodes={anakDari(n.jabatan)} anakDari={anakDari} />
    </TreeNode>
  ));
}

export default function StrukturBagan({ data }) {
  const org = Array.isArray(data?.organisasi) ? data.organisasi : [];
  const anakDari = (jabatan) => org.filter((o) => (o.parent ?? '') === jabatan);
  const akar = org.filter((o) => !o.parent || !org.some((x) => x.jabatan === o.parent));

  if (org.length === 0) {
    return <p className="py-10 text-center text-sm text-slate-400">Struktur organisasi belum diisi.</p>;
  }

  return (
    <div className="overflow-x-auto pb-2">
      <div className="flex min-w-max flex-col items-center gap-8 px-4">
        {akar.map((r) => (
          <Tree key={r.jabatan} lineWidth="1px" lineColor="rgba(33,118,189,0.25)" lineBorderRadius="8px"
                label={<div className="inline-flex"><Kotak node={r} akar /></div>}>
            <Cabang nodes={anakDari(r.jabatan)} anakDari={anakDari} />
          </Tree>
        ))}
      </div>
    </div>
  );
}
