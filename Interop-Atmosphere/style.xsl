<?xml version='1.0' encoding="utf-8" ?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="html" encoding="UTF-8" indent="yes"/>
	<xsl:strip-space elements="previsions"/>
	
	
	<xsl:variable name="converteur_temperature">273.15</xsl:variable>

	<xsl:template match="/">
		
		<xsl:variable name="currentDate">
			<xsl:value-of select="substring(previsions/echeance/@timestamp, 1, 10)"/>
		</xsl:variable>
			
		<html>
			<head/>
			<body>
				<h1>Météo du <xsl:value-of select="$currentDate"/></h1>
				<xsl:apply-templates select="previsions/echeance">
					
					<xsl:with-param name="currentDate" select="$currentDate" />
				</xsl:apply-templates>
			</body>
		</html>
	</xsl:template>
	
	
	<xsl:template match="echeance">
		<xsl:param name="currentDate" />

		<xsl:choose>
			
			<xsl:when test="substring(@timestamp, 1, 10) = $currentDate and (substring(@timestamp, 12, 2) = '07' or substring(@timestamp, 12, 2) = '13' or substring(@timestamp, 12, 2) = '19')">
				<div>
					<h2><xsl:value-of select="substring(@timestamp, 12, 5)"/></h2>
					<ul>
						<li>Température : <xsl:apply-templates select="temperature/level" /></li>
						<li>Risque pluie : <xsl:apply-templates select="pluie"/></li>
						<li>Risque neige : <xsl:value-of select="risque_neige"/></li>
						<li>Vent : <xsl:apply-templates select="vent_moyen"/></li>
					</ul>
				</div>
			</xsl:when>
			<xsl:otherwise>
				
				<xsl:if test="position() = 1 and not(substring(@timestamp, 1, 10) = $currentDate)">
					<p>La première valeur de l'api est celle de la veille, cela peut expliquer pourquoi la météo n'est pas affichée.</p>
				</xsl:if>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	
	<xsl:template match="temperature/level">
		<xsl:if test="@val = 'sol'">
			<xsl:value-of select="round(. - $converteur_temperature)"/> °C
		</xsl:if>
	</xsl:template>
	
	
	<xsl:template match="pluie">
		<xsl:choose>
			<xsl:when test=". = 0">
				non
			</xsl:when>
			<xsl:otherwise>
				oui
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	
	<xsl:template match="vent_moyen">
		<xsl:value-of select="level/."/> km/h
	</xsl:template>

</xsl:stylesheet>