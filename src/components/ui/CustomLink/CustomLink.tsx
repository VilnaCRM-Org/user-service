import { ReactNode } from 'react';
import { LinkProps as MuiLinkProps } from '@mui/material';
import Link from 'next/link';

interface ILinkProps extends MuiLinkProps {
  href: string;
  children: ReactNode;
  color?: string;
}

export function CustomLink({ href, children, ...props }: ILinkProps) {
  return (
    <Link href={href} {...props}>
      {children}
    </Link>
  );
}
