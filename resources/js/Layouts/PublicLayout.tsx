import { Link, usePage } from '@inertiajs/react';
import { AppBar, Box, Button, Container, Drawer, IconButton, Stack, Toolbar, Typography } from '@mui/material';
import MenuRounded from '@mui/icons-material/MenuRounded';
import CloseRounded from '@mui/icons-material/CloseRounded';
import { ReactNode, useState } from 'react';
import { PageProps } from '@/types';

const links = [['Home', '/'], ['About', '/about'], ['Academics', '/academics'], ['Teachers', '/teachers'], ['Facilities', '/facilities'], ['Admissions', '/admissions'], ['News & Events', '/news-events'], ['Gallery', '/gallery'], ['Contact', '/contact']];

export default function PublicLayout({ children }: { children: ReactNode }) {
    const [open, setOpen] = useState(false);
    const { auth, school } = usePage<PageProps>().props;
    const socialLinks = [['Facebook', school.facebook_url], ['Instagram', school.instagram_url], ['YouTube', school.youtube_url]].filter(([, href]) => href);
    return <Box>
        <Box sx={{ bgcolor: '#06372b', color: 'white', py: .8 }}><Container maxWidth="xl"><Stack direction="row" justifyContent="space-between" sx={{ fontSize: 12 }}><span>☎ {school.phone} &nbsp; • &nbsp; {school.email}</span><span>EIIN {school.eiin || '—'} &nbsp; | &nbsp; বাংলা / EN</span></Stack></Container></Box>
        <AppBar position="sticky" color="inherit" elevation={0} sx={{ borderBottom: '1px solid', borderColor: 'divider', bgcolor: 'rgba(251,250,246,.94)', backdropFilter: 'blur(14px)' }}>
            <Container maxWidth="xl"><Toolbar disableGutters sx={{ minHeight: 76, gap: 3 }}>
                <Stack component={Link} href="/" direction="row" alignItems="center" spacing={1.5} sx={{ textDecoration: 'none', flexGrow: { xs: 1, lg: 0 } }}>
                    {school.logo_path ? <Box component="img" src={`/storage/${school.logo_path}`} alt={`${school.school_name} logo`} sx={{ width:44,height:44,objectFit:'contain' }} /> : <Box sx={{ width: 44, height: 44, borderRadius: '50% 50% 46% 54%', bgcolor: 'primary.main', color: 'white', display: 'grid', placeItems: 'center', fontFamily: 'serif', fontWeight: 700, fontSize: 22, border: '2px solid #caa85b' }}>{school.short_name.slice(0, 2).toUpperCase()}</Box>}
                    <Box><Typography fontWeight={800} color="primary.dark" lineHeight={1.05}>{school.short_name.toUpperCase()}</Typography><Typography variant="caption" color="text.secondary">{school.school_name}</Typography></Box>
                </Stack>
                <Stack direction="row" spacing={.3} sx={{ ml: 'auto', display: { xs: 'none', lg: 'flex' } }}>{links.map(([label, href]) => <Button key={href} component={Link} href={href} color="inherit" size="small">{label}</Button>)}</Stack>
                <Button component={Link} href={auth.user ? auth.user.role?.slug === 'teacher' ? '/teacher' : auth.user.role?.slug === 'guardian' ? '/guardian' : auth.user.role?.slug === 'student' ? '/student' : '/admin' : '/login'} variant="contained" sx={{ display: { xs: 'none', sm: 'inline-flex' } }}>{auth.user ? 'My portal' : 'Portal login'}</Button>
                <IconButton onClick={() => setOpen(true)} sx={{ display: { lg: 'none' } }} aria-label="Open navigation"><MenuRounded /></IconButton>
            </Toolbar></Container>
        </AppBar>
        <Drawer anchor="right" open={open} onClose={() => setOpen(false)}><Box sx={{ width: 300, p: 3 }}><Stack direction="row" justifyContent="space-between"><Typography fontWeight={800}>Menu</Typography><IconButton onClick={() => setOpen(false)}><CloseRounded /></IconButton></Stack><Stack mt={3}>{links.map(([label, href]) => <Button key={href} component={Link} href={href} onClick={() => setOpen(false)} sx={{ justifyContent: 'flex-start', py: 1.5 }}>{label}</Button>)}</Stack></Box></Drawer>
        <main>{children}</main>
        <Box component="footer" sx={{ bgcolor: '#062f25', color: '#eaf2ee', pt: 9, pb: 4 }}><Container maxWidth="lg"><Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: '2fr 1fr 1fr' }, gap: 6 }}><Box><Typography variant="h5" fontFamily="Newsreader" gutterBottom>Learning with purpose.<br/>Leading with character.</Typography><Typography color="#b8cbc4" maxWidth={480}>A caring English and Bangla medium learning community in Dhaka, grounded in Bangladeshi values and prepared for a changing world.</Typography>{socialLinks.length > 0 && <Stack direction="row" spacing={2} mt={2}>{socialLinks.map(([label, href]) => <Typography key={label} component="a" href={href} target="_blank" rel="noreferrer" color="#e1c16e" sx={{textDecoration:'none',fontWeight:700}}>{label}</Typography>)}</Stack>}</Box><Box><Typography fontWeight={700} mb={2}>Explore</Typography>{links.slice(1,5).map(([l,h]) => <Typography key={h} component={Link} href={h} display="block" sx={{ textDecoration: 'none', mb: 1, color:'#b8cbc4' }}>{l}</Typography>)}</Box><Box><Typography fontWeight={700} mb={2}>Visit us</Typography><Typography color="#b8cbc4">{school.address}<br/>{school.office_hours}<br/>{school.phone}<br/>{school.email}</Typography></Box></Box><Box sx={{ borderTop: '1px solid #31564c', mt: 7, pt: 3 }}><Typography variant="caption" color="#9ab3aa">© {new Date().getFullYear()} {school.school_name}. Safeguarding every learner.</Typography></Box></Container></Box>
    </Box>;
}
