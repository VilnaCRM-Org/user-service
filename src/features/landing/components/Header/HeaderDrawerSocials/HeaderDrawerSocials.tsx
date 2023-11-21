import { Box } from '@mui/material';

import CustomLink from '@/components/ui/CustomLink/CustomLink';

const SOCIALS = [
  { id: 'instagram', url: '/assets/svg/header-drawer/socials/instagram.svg', href: '/' },
  { id: 'github', url: '/assets/svg/header-drawer/socials/github.svg', href: '/' },
  { id: 'facebook', url: '/assets/svg/header-drawer/socials/facebook.svg', href: '/' },
  { id: 'linked-in', url: '/assets/svg/header-drawer/socials/linked-in.svg', href: '/' },
];

export default function HeaderDrawerSocials() {
  return (
    <Box sx={{ display: 'flex', justifyContent: 'center', gap: '30px' }}>
      {SOCIALS.map(({ url, id, href }) => (
        <CustomLink key={id} href={href}>
          <img src={url} alt={id}/>
        </CustomLink>
      ))}
    </Box>
  );
}
