import { AppBar, Container } from '@mui/material';
import Image from 'next/image';

import ToolBar from '@/components/ui/UIToolBar/UIToolBar';

import Logo from '../../assets/svg/Logo/Logo.svg';

import AuthenticationButtons from './AuthenticationButtons/AuthenticationButtons';
import Drawer from './Drawer/Drawer';
import NavLink from './NavLink/NavLink';

const links = [
  { id: 1, value: 'Переваги' },
  { id: 2, value: 'Для кого' },
  { id: 3, value: 'Інтеграція' },
  { id: 4, value: 'Контакти' },
];

function Header() {
  return (
    <AppBar
      position="static"
      sx={{
        backgroundColor: 'white',
        boxShadow: 'none',
        position: 'fixed',
        zIndex: 1000,
      }}
    >
      <Container maxWidth="xl">
        <ToolBar>
          <Image src={Logo} alt="Header Image" width={131} height={44} />
          <NavLink links={links} />
          <AuthenticationButtons />
          <Drawer />
        </ToolBar>
      </Container>
    </AppBar>
  );
}

export default Header;
