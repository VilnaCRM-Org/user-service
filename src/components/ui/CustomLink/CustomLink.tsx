import { LinkProps as MuiLinkProps } from '@mui/material';
import Link from 'next/link';
import React, { ReactNode } from 'react';

interface ILinkProps extends MuiLinkProps {
  href: string;
  children: ReactNode;
  replace?: boolean;
  scroll?: boolean;
  legacyBehavior?: boolean;
  passHref?: boolean;
  shallow?: boolean;
  color?: string;
  style?: React.CSSProperties;
}

export default function CustomLink({
  href,
  children,
  scroll = true,
  replace = false,
  legacyBehavior = false,
  shallow = false,
  passHref = false,
  color = 'inherit',
  style = {},
}: ILinkProps) {
  return (
    <Link
      href={href}
      replace={replace}
      scroll={scroll}
      legacyBehavior={legacyBehavior}
      shallow={shallow}
      passHref={passHref}
      style={{ ...style, color }}
    >
      {children}
    </Link>
  );
}

CustomLink.defaultProps = {
  replace: false,
  scroll: true,
  passHref: false,
  legacyBehavior: false,
  shallow: false,
  color: 'inherit',
  style: {},
};
