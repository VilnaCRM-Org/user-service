import React from 'react';
import {useTranslation} from 'react-i18next';
import {LandingComponent} from "@/features/landing";

export default function Home() {
  const {t} = useTranslation();

  return (
    <LandingComponent/>
  );
}
