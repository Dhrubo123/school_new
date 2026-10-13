import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';
import CloudUploadOutlined from '@mui/icons-material/CloudUploadOutlined';
import SaveOutlined from '@mui/icons-material/SaveOutlined';
import { Alert, Box, Button, Card, Divider, Stack, TextField, Typography } from '@mui/material';
import { ChangeEvent, FormEvent, useState } from 'react';

type Settings = Record<string, string>;

export default function Edit({ settings }: { settings: Settings }) {
    const form = useForm({
        school_name: settings.school_name || '', short_name: settings.short_name || '', eiin: settings.eiin || '',
        phone: settings.phone || '', email: settings.email || '', address: settings.address || '', office_hours: settings.office_hours || '', map_embed_url: settings.map_embed_url || '',
        facebook_url: settings.facebook_url || '', instagram_url: settings.instagram_url || '', youtube_url: settings.youtube_url || '', logo: null as File | null, _method: '',
    });
    const [preview, setPreview] = useState(settings.logo_path ? `/storage/${settings.logo_path}` : '');
    const chooseLogo = (event: ChangeEvent<HTMLInputElement>) => {
        const logo = event.target.files?.[0] || null;
        form.setData('logo', logo);
        if (logo) setPreview(URL.createObjectURL(logo));
    };
    const submit = (event: FormEvent) => { event.preventDefault(); form.transform((data) => ({ ...data, _method: 'put' })); form.post('/admin/settings', { forceFormData: true }); };
    const field = (key: Exclude<keyof typeof form.data, 'logo'>, label: string, props: Record<string, unknown> = {}) => <TextField label={label} value={form.data[key]} onChange={event => form.setData(key, event.target.value)} error={!!form.errors[key]} helperText={form.errors[key]} {...props} />;

    return <AdminLayout><Head title="School settings" />
        <Box mb={4}><Typography variant="h3" fontFamily="Newsreader">School settings</Typography><Typography color="text.secondary">Manage the school identity and contact details shown across the public website.</Typography></Box>
        <Stack component="form" onSubmit={submit} spacing={3} maxWidth={1000}>
            <Card sx={{p:{xs:3,md:4}}}><Typography variant="h5" fontWeight={800}>School identity</Typography><Typography color="text.secondary" variant="body2" mb={3}>Your formal school name, short display name, and logo.</Typography><Box sx={{display:'grid',gridTemplateColumns:{xs:'1fr',md:'1fr 1fr'},gap:2}}>{field('school_name', 'School name')}{field('short_name', 'Short display name')}{field('eiin', 'EIIN (optional)')}</Box><Divider sx={{my:3}}/><Stack direction={{xs:'column',sm:'row'}} spacing={3} alignItems={{sm:'center'}}>{preview ? <Box component="img" src={preview} alt="Current school logo" sx={{width:100,height:100,objectFit:'contain',border:'1px solid',borderColor:'divider',borderRadius:2,p:1}} /> : <Box sx={{width:100,height:100,borderRadius:2,bgcolor:'primary.main',color:'white',display:'grid',placeItems:'center',fontSize:30,fontWeight:850}}>{form.data.short_name.slice(0,2).toUpperCase()}</Box>}<Box><Button component="label" variant="outlined" startIcon={<CloudUploadOutlined />}>Upload logo<input hidden type="file" accept="image/jpeg,image/png,image/webp" onChange={chooseLogo} /></Button><Typography variant="caption" color="text.secondary" display="block" mt={1}>PNG, JPG, or WebP · maximum 2 MB.</Typography>{form.errors.logo && <Alert severity="error" sx={{mt:1}}>{form.errors.logo}</Alert>}</Box></Stack></Card>
            <Card sx={{p:{xs:3,md:4}}}><Typography variant="h5" fontWeight={800}>Contact details</Typography><Typography color="text.secondary" variant="body2" mb={3}>Used in the website header, footer and contact information.</Typography><Box sx={{display:'grid',gridTemplateColumns:{xs:'1fr',md:'1fr 1fr'},gap:2}}>{field('phone', 'Phone number')}{field('email', 'Public email address')}{field('address', 'Campus address', { multiline:true, rows:3, sx:{gridColumn:{md:'span 2'}} })}{field('office_hours', 'Office hours', { sx:{gridColumn:{md:'span 2'}} })}{field('map_embed_url', 'Google Maps embed URL (optional)', { placeholder:'https://www.google.com/maps/embed?...', sx:{gridColumn:{md:'span 2'}}, helperText:'In Google Maps, choose Share → Embed a map, then paste the iframe URL here.' })}</Box></Card>
            <Card sx={{p:{xs:3,md:4}}}><Typography variant="h5" fontWeight={800}>Social links</Typography><Typography color="text.secondary" variant="body2" mb={3}>Optional public links. Leave a field blank to hide it.</Typography><Stack spacing={2}>{field('facebook_url', 'Facebook page URL')}{field('instagram_url', 'Instagram profile URL')}{field('youtube_url', 'YouTube channel URL')}</Stack></Card>
            <Button type="submit" variant="contained" size="large" startIcon={<SaveOutlined />} disabled={form.processing} sx={{alignSelf:'flex-start'}}>{form.processing ? 'Saving…' : 'Save settings'}</Button>
        </Stack>
    </AdminLayout>;
}
