import PublicLayout from '@/Layouts/PublicLayout';
import { Head } from '@inertiajs/react';
import { Box, Card, Container, Stack, Typography } from '@mui/material';
import AutoStoriesRounded from '@mui/icons-material/AutoStoriesRounded';

type Teacher = {
    id: number;
    designation?: string;
    photo_path?: string;
    bio?: string;
    user: { name: string };
};

const initials = (name: string) => name.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase();

export default function Teachers({ teachers = [] }: { teachers?: Teacher[] }) {
    return <PublicLayout>
        <Head title="Meet our teachers"><meta name="description" content="Meet the dedicated educators who guide and support our learners every day." /></Head>
        <Box sx={{ bgcolor: 'primary.dark', color: 'white', py: { xs: 9, md: 14 }, position: 'relative', overflow: 'hidden' }}>
            <Box sx={{ position: 'absolute', width: 380, height: 380, border: '48px solid', borderColor: 'secondary.main', opacity: .18, borderRadius: '50%', right: { xs: -220, md: 90 }, top: -180 }} />
            <Container maxWidth="lg" sx={{ position: 'relative' }}>
                <Typography variant="overline" color="#e1c16e" fontWeight={800}>OUR EDUCATORS</Typography>
                <Typography variant="h1" maxWidth={780} sx={{ fontSize: { xs: 48, md: 76 }, mt: 2 }}>Teachers who help every learner thrive.</Typography>
                <Typography color="#c2d5ce" fontSize={20} maxWidth={680} mt={3}>Our educators bring subject knowledge, care and high expectations to every classroom and conversation.</Typography>
            </Container>
        </Box>
        <Container maxWidth="lg" sx={{ py: { xs: 8, md: 12 } }}>
            {teachers.length ? <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: 'repeat(2, 1fr)', lg: 'repeat(3, 1fr)' }, gap: { xs: 2.5, md: 3.5 } }}>
                {teachers.map((teacher) => <Card key={teacher.id} sx={{ p: 3, display: 'flex', flexDirection: 'column', minHeight: 390, border: '1px solid', borderColor: 'divider', boxShadow: 'none', transition: 'transform .2s ease, box-shadow .2s ease', '&:hover': { transform: 'translateY(-5px)', boxShadow: '0 16px 36px rgba(6,55,43,.12)' } }}>
                    {teacher.photo_path ? <Box component="img" src={`/storage/${teacher.photo_path}`} alt={teacher.user.name} loading="lazy" sx={{ width: '100%', aspectRatio: '5 / 4', objectFit: 'cover', borderRadius: 2.5, bgcolor: 'primary.light' }} /> : <Box sx={{ width: '100%', aspectRatio: '5 / 4', borderRadius: 2.5, bgcolor: '#dce9e2', color: 'primary.dark', display: 'grid', placeItems: 'center', fontFamily: 'Newsreader', fontSize: 56, fontWeight: 700 }}>{initials(teacher.user.name)}</Box>}
                    <Box pt={3}>
                        <Typography color="secondary.dark" fontWeight={800} variant="overline">{teacher.designation || 'Teacher'}</Typography>
                        <Typography variant="h4" fontFamily="Newsreader" mt={.5}>{teacher.user.name}</Typography>
                        {teacher.bio && <Typography color="text.secondary" lineHeight={1.7} mt={1.5}>{teacher.bio}</Typography>}
                    </Box>
                </Card>)}
            </Box> : <Card sx={{ p: { xs: 4, md: 7 }, textAlign: 'center', bgcolor: '#f4f1e8', boxShadow: 'none' }}>
                <AutoStoriesRounded sx={{ fontSize: 48, color: 'secondary.dark' }} />
                <Typography variant="h3" fontFamily="Newsreader" mt={2}>Our staff profiles are coming soon.</Typography>
                <Typography color="text.secondary" maxWidth={500} mx="auto" mt={2}>Please check back soon to meet the people who make our learning community special.</Typography>
            </Card>}
            <Stack direction={{ xs: 'column', md: 'row' }} spacing={3} alignItems={{ md: 'center' }} justifyContent="space-between" sx={{ bgcolor: '#f4f1e8', borderRadius: 3, p: { xs: 3, md: 5 }, mt: { xs: 6, md: 9 } }}>
                <Box><Typography variant="h4" fontFamily="Newsreader">A team united by care and curiosity.</Typography><Typography color="text.secondary" mt={1}>We work closely with families so every child feels known, challenged and supported.</Typography></Box>
                <Typography color="primary.main" fontWeight={800} whiteSpace="nowrap">Learn · Grow · Belong</Typography>
            </Stack>
        </Container>
    </PublicLayout>;
}
