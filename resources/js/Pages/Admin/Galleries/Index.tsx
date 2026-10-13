import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Box, Button, Card, Chip, IconButton, Pagination, Stack, TextField, Typography } from '@mui/material';
import AddPhotoAlternateOutlined from '@mui/icons-material/AddPhotoAlternateOutlined';
import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import EditRounded from '@mui/icons-material/EditRounded';
import ImageOutlined from '@mui/icons-material/ImageOutlined';
import { FormEvent, useState } from 'react';

type Gallery = { id:number; title:string; description?:string; is_published:boolean; published_at?:string; images_count:number };
type Props = { search:string; galleries:{data:Gallery[];current_page:number;last_page:number} };

export default function Index({ search, galleries }: Props) {
    const [query, setQuery] = useState(search);
    const submit = (event: FormEvent) => { event.preventDefault(); router.get('/admin/galleries', { search: query }, { preserveState: true, replace: true }); };
    return <AdminLayout><Head title="Gallery albums" />
        <Stack direction={{xs:'column',sm:'row'}} justifyContent="space-between" mb={4}><Box><Typography variant="h3" fontFamily="Newsreader">Gallery albums</Typography><Typography color="text.secondary">Curate and publish the moments that make up school life.</Typography></Box><Button component={Link} href="/admin/galleries/create" variant="contained" startIcon={<AddPhotoAlternateOutlined/>}>Create album</Button></Stack>
        <Card sx={{p:3}}><Box component="form" onSubmit={submit} mb={3}><TextField value={query} onChange={event=>setQuery(event.target.value)} placeholder="Search albums…" sx={{width:{xs:'100%',sm:380}}}/></Box>
        {galleries.data.length ? <Box sx={{display:'grid',gridTemplateColumns:{xs:'1fr',md:'repeat(2,1fr)'},gap:2}}>{galleries.data.map(gallery=><Card key={gallery.id} variant="outlined" sx={{p:3,boxShadow:'none'}}><Stack direction="row" justifyContent="space-between" spacing={2}><Box minWidth={0}><Stack direction="row" spacing={1} alignItems="center" flexWrap="wrap"><Typography variant="h6" fontWeight={750}>{gallery.title}</Typography><Chip size="small" label={gallery.is_published?'Published':'Draft'} color={gallery.is_published?'primary':'default'}/></Stack><Typography color="text.secondary" variant="body2" mt={1}>{gallery.description || 'No description added.'}</Typography></Box><Box sx={{width:56,height:56,borderRadius:2,bgcolor:'primary.light',display:'grid',placeItems:'center',color:'primary.main',flexShrink:0}}><ImageOutlined/></Box></Stack><Stack direction="row" justifyContent="space-between" alignItems="center" mt={3}><Typography variant="body2" color="text.secondary">{gallery.images_count} photograph{gallery.images_count===1?'':'s'}</Typography><Stack direction="row"><IconButton component={Link} href={`/admin/galleries/${gallery.id}/edit`} aria-label={`Edit ${gallery.title}`}><EditRounded/></IconButton><IconButton color="error" onClick={()=>confirm(`Delete “${gallery.title}” and all its photographs?`)&&router.delete(`/admin/galleries/${gallery.id}`)} aria-label={`Delete ${gallery.title}`}><DeleteOutlineRounded/></IconButton></Stack></Stack></Card>)}</Box> : <Box textAlign="center" py={8}><ImageOutlined color="disabled" sx={{fontSize:48}}/><Typography variant="h6" mt={2}>No gallery albums yet</Typography><Typography color="text.secondary">Create an album, then upload photographs for it.</Typography></Box>}
        <Pagination count={galleries.last_page} page={galleries.current_page} onChange={(_,page)=>router.get('/admin/galleries',{search:query,page},{preserveState:true})} sx={{mt:3}}/></Card>
    </AdminLayout>;
}
