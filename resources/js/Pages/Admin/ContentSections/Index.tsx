import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import AddRounded from '@mui/icons-material/AddRounded';
import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import EditRounded from '@mui/icons-material/EditRounded';
import { Box, Button, Card, Chip, MenuItem, Stack, TextField, Typography } from '@mui/material';

type Section = { id:number; page_slug:string; section_key:string; title:string; body?:string; is_published:boolean; sort_order:number };
type Props = { page:string; pages:string[]; sections:Section[] };
const pageName = (page:string) => page === 'home' ? 'Homepage' : page.charAt(0).toUpperCase() + page.slice(1);

export default function Index({ page, pages, sections }: Props) {
    return <AdminLayout><Head title="Website content" />
        <Stack direction={{xs:'column',sm:'row'}} justifyContent="space-between" spacing={2} mb={4}><Box><Typography variant="h3" fontFamily="Newsreader">Website content</Typography><Typography color="text.secondary">Edit the written content displayed on the public school website.</Typography></Box><Button component={Link} href={`/admin/content/create?page=${page}`} variant="contained" startIcon={<AddRounded />}>Add section</Button></Stack>
        <Card sx={{p:{xs:2,md:3}}}><TextField select label="Editing page" value={page} onChange={event => router.get('/admin/content', {page:event.target.value})} sx={{minWidth:{xs:'100%',sm:240},mb:3}}>{pages.map(item => <MenuItem key={item} value={item}>{pageName(item)}</MenuItem>)}</TextField><Stack spacing={1.5}>{sections.map(section => <Card key={section.id} variant="outlined" sx={{p:2.5,boxShadow:'none'}}><Stack direction={{xs:'column',sm:'row'}} spacing={2} alignItems={{sm:'center'}}><Box flexGrow={1}><Stack direction="row" spacing={1} alignItems="center" flexWrap="wrap"><Typography fontWeight={800}>{section.title}</Typography><Chip size="small" label={section.is_published ? 'Published' : 'Hidden'} color={section.is_published ? 'primary' : 'default'} /><Chip size="small" label={`Order ${section.sort_order}`} variant="outlined" /></Stack><Typography variant="caption" color="text.secondary">Key: {section.section_key}</Typography><Typography color="text.secondary" variant="body2" mt={.75} whiteSpace="pre-wrap">{section.body || 'No body text added.'}</Typography></Box><Stack direction="row"><Button component={Link} href={`/admin/content/${section.id}/edit`} startIcon={<EditRounded />}>Edit</Button><Button color="error" onClick={() => confirm(`Delete “${section.title}”?`) && router.delete(`/admin/content/${section.id}`)} startIcon={<DeleteOutlineRounded />}>Delete</Button></Stack></Stack></Card>)}</Stack>{!sections.length && <Box textAlign="center" py={8}><Typography variant="h6">No sections on this page</Typography><Typography color="text.secondary" mt={1}>Add a section to publish content on this page.</Typography></Box>}</Card>
    </AdminLayout>;
}
